<?php
/**
 * 本地 V1 模型 API
 * ----------------------------------------------------------
 * 取代 https://api.live2dweb.com/model/v2 这一远端鉴权 + 清单服务,
 * 直接读取 model/{name}/ 下已下载的 V1 模型文件并返回 JSON 清单。
 *
 * 接口前缀:/wp-json/live2d/v1/model
 * 路由形态故意对齐 fghrsh/live2d_api,使 live2d-tips.ts 的 ApiUrlType.Other
 * 分支无需任何 TS 修改:
 *   GET /get/?id={modelName}-{textureId}             -> V1 manifest(textures 已替换 + 路径重写为绝对 URL)
 *   GET /rand/?id={modelName}                        -> { model: { id, message } }
 *   GET /switch/?id={modelName}                      -> { model: { id, message } }
 *   GET /rand_textures/?id={modelName}-{textureId}   -> { textures: { id, message } }
 *   GET /switch_textures/?id={modelName}-{textureId} -> { textures: { id, message } }
 *
 * 数据来源:
 *   1) live2d_SDK::GetV1Catalog() —— 模型条目元数据(name/skins/texturesJson)
 *   2) DOWNLOAD_DIR/{modelName}/index.json —— 由 V1 zip 解压而来的官方 manifest
 *   3) {plugin_root}/assets/v1/{modelName}.textures.json —— 多套贴图清单(可选)
 *
 * 鉴权:全部 public(__return_true)。访客只能看到管理员通过 Shop 下载到本地
 * 的模型;模型文件本身公开可读(/wp-content/plugins/live-2d/model/...),
 * 因此在此处再加一道授权并不能阻止资源访问,只会让正常前端流程走不通。
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once(__DIR__ . '/live2d-SDK.php');

if (!function_exists('live2d_v1api_local_url')) {
	/**
	 * 返回本地 V1 模型 API 的根 URL(无尾斜杠),前端会在其后拼
	 * `/get/?id=X-Y`、`/rand/?id=X` 等(详见 live2d-tips.ts ApiUrlType.Other 分支)。
	 *
	 * - pretty permalink:  https://site.com/wp-json/live2d/v1/model
	 * - plain  permalink:  https://site.com/?rest_route=/live2d/v1/model
	 *   (plain 模式下前端拼出 `?rest_route=/.../get/?id=...` 会把 `?id=` 当成
	 *    rest_route 值的一部分,REST 路由因此匹配失败。该插件其余 token/refresh
	 *    流程也都使用 rest_url,因此 plain permalink 在本插件场景下不被支持。)
	 */
	function live2d_v1api_local_url()
	{
		return untrailingslashit(rest_url('live2d/v1/model'));
	}
}

if (!function_exists('live2d_normalize_api_type')) {
	/**
	 * 把 DB 中的 `apiType` 归一化成三态字符串:
	 *   - 'local'  : 本地部署旧版模型(由插件 model/ 目录托管,走本地 REST)
	 *   - 'remote' : 自行部署旧版模型(用户给的 fghrsh-style 旧 PHP API URL)
	 *   - 'custom' : 自定义新版模型路径(.model3.json 直链或 V3 目录根)
	 *
	 * 兼容历史值:
	 *   - bool true  / 字符串 "1" / "true"  → 'local'  (原"创意工坊")
	 *   - bool false / 字符串 "0" / "false" / null / 缺失 → 'remote' (原"自定API")
	 */
	function live2d_normalize_api_type($value)
	{
		if (is_string($value)) {
			$v = strtolower(trim($value));
			if (in_array($v, array('local', 'remote', 'custom'), true)) {
				return $v;
			}
			if ($v === '1' || $v === 'true' || $v === 'on') return 'local';
			if ($v === '0' || $v === 'false' || $v === 'off' || $v === '') return 'remote';
		}
		if ($value === true)  return 'local';
		if ($value === false) return 'remote';
		return 'remote';
	}
}

if (!function_exists('live2d_api_type_is_local')) {
	/** apiType 是否为本地部署旧版模型(对应原"创意工坊" truthy 行为) */
	function live2d_api_type_is_local($value)
	{
		return live2d_normalize_api_type($value) === 'local';
	}
}

class live2d_V1Api
{
	const REST_NS    = 'live2d/v1';
	const REST_GROUP = 'model';

	/** REST 路由注册:挂在插件主文件的 rest_api_init 钩子里调用一次即可。 */
	public static function register_routes()
	{
		$self = new self();
		$args = array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'required' => true,
					'type'     => 'string',
				),
			),
		);
		register_rest_route(self::REST_NS, '/' . self::REST_GROUP . '/get', array_merge($args, array(
			'callback' => array($self, 'get_manifest'),
		)));
		register_rest_route(self::REST_NS, '/' . self::REST_GROUP . '/rand', array_merge($args, array(
			'callback' => array($self, 'rand_model'),
		)));
		register_rest_route(self::REST_NS, '/' . self::REST_GROUP . '/switch', array_merge($args, array(
			'callback' => array($self, 'switch_model'),
		)));
		register_rest_route(self::REST_NS, '/' . self::REST_GROUP . '/rand_textures', array_merge($args, array(
			'callback' => array($self, 'rand_textures'),
		)));
		register_rest_route(self::REST_NS, '/' . self::REST_GROUP . '/switch_textures', array_merge($args, array(
			'callback' => array($self, 'switch_textures'),
		)));
	}

	/**
	 * GET /get/?id={modelName}-{textureId}
	 * 返回前端 LAppModel.load 直接消费的 V1 manifest:
	 *   - textures 已按 textureId 替换(若有 *.textures.json 或 skins 子目录)
	 *   - 所有相对路径(model/textures/physics/pose/expressions/motions)已重写为绝对 URL
	 *   - 顶部加一个 Version: 1 字段,让 live2d-tips.ts 走 loadv1 分支
	 */
	public function get_manifest(WP_REST_Request $request)
	{
		list($modelName, $textureId) = $this->parse_id_pair($request->get_param('id'));
		$item = $this->find_catalog_item($modelName);
		if ($item === null) {
			return new WP_REST_Response(array('error' => 'model_not_found', 'id' => $modelName), 404);
		}

		// 多皮肤型(skins): textureId 实际上是子目录索引,直接换基础目录。
		// 普通型: 基础目录就是 model/{name},textureId 用来选 *.textures.json 中的一组。
		if (!empty($item['skins'])) {
			$skinIndex = max(0, min(count($item['skins']) - 1, intval($textureId)));
			$skin = $item['skins'][$skinIndex];
			$relDir = $modelName . '/' . $skin;
		} else {
			$relDir = $modelName;
		}

		$absDir = DOWNLOAD_DIR . $relDir . '/';
		$indexJsonPath = $absDir . 'index.json';
		if (!file_exists($indexJsonPath)) {
			return new WP_REST_Response(array(
				'error'   => 'index_json_missing',
				'id'      => $modelName,
				'expects' => $indexJsonPath,
			), 404);
		}

		$manifest = $this->read_json_file($indexJsonPath);
		if (!is_array($manifest)) {
			return new WP_REST_Response(array(
				'error' => 'index_json_invalid',
				'id'    => $modelName,
			), 500);
		}

		// 普通型:按 textureId 从 assets/v1/{name}.textures.json 替换 textures 字段。
		// 多皮肤型不替换,直接用子目录 index.json 自带的 textures。
		if (empty($item['skins']) && !empty($item['texturesJson'])) {
			$this->apply_textures_json($manifest, $modelName, intval($textureId));
		}

		// 把所有相对路径替换为绝对 URL。
		$baseUrl = $this->plugin_model_url() . '/' . live2d_v1api_rawurlencode_path($relDir) . '/';
		$this->rewrite_manifest_paths($manifest, $baseUrl);

		// 标记为 V1,让前端 ver = "1.0.0" → loadv1 分支。
		$manifest['Version'] = 1;

		return new WP_REST_Response($manifest, 200);
	}

	/**
	 * GET /rand/?id={modelName}
	 * 在已下载模型列表中随机挑一个不同的 modelName 返回。
	 * 列表为空 / 只有 1 个时回退到原 id。
	 */
	public function rand_model(WP_REST_Request $request)
	{
		$modelName = $this->parse_id_pair($request->get_param('id'))[0];
		$models    = $this->list_downloaded_models();
		$next      = $this->pick_random_other($models, $modelName);
		$item      = $this->find_catalog_item($next);
		$label     = $item ? $item['label'] : $next;
		return new WP_REST_Response(array(
			'model' => array(
				'id'      => $next,
				/* translators: %s: model display name */
				'message' => sprintf( __('我的新名字叫做「%s」哦~', 'live-2d'), $label ),
			),
		), 200);
	}

	/**
	 * GET /switch/?id={modelName}
	 * 在已下载模型列表中按字母序取下一个 modelName。
	 */
	public function switch_model(WP_REST_Request $request)
	{
		$modelName = $this->parse_id_pair($request->get_param('id'))[0];
		$models    = $this->list_downloaded_models();
		$next      = $this->pick_next_in_list($models, $modelName);
		$item      = $this->find_catalog_item($next);
		$label     = $item ? $item['label'] : $next;
		return new WP_REST_Response(array(
			'model' => array(
				'id'      => $next,
				/* translators: %s: model display name */
				'message' => sprintf( __('我的新名字叫做「%s」哦~', 'live-2d'), $label ),
			),
		), 200);
	}

	/**
	 * GET /rand_textures/?id={modelName}-{textureId}
	 * 在该模型可用的 textureId 集合里随机一个不同的 id。只有 1 套时回退到原 id,前端会
	 * 根据 result.textures.id 是否变化展示「没有衣服可换」的提示。
	 */
	public function rand_textures(WP_REST_Request $request)
	{
		list($modelName, $textureId) = $this->parse_id_pair($request->get_param('id'));
		$count = $this->texture_count($modelName);
		$next  = $this->pick_random_other_int(intval($textureId), $count);
		return new WP_REST_Response(array(
			'textures' => array(
				'id'      => $next,
				'message' => $next === intval($textureId)
					? __('我还没有其他衣服呢~', 'live-2d')
					: __('你帮我换了一件新衣服!', 'live-2d'),
			),
		), 200);
	}

	/**
	 * GET /switch_textures/?id={modelName}-{textureId}
	 * 顺序切换到下一个 textureId,到末尾循环回 0。
	 */
	public function switch_textures(WP_REST_Request $request)
	{
		list($modelName, $textureId) = $this->parse_id_pair($request->get_param('id'));
		$count = $this->texture_count($modelName);
		if ($count <= 1) {
			return new WP_REST_Response(array(
				'textures' => array(
					'id'      => intval($textureId),
					'message' => __('我还没有其他衣服呢~', 'live-2d'),
				),
			), 200);
		}
		$next = (intval($textureId) + 1) % $count;
		return new WP_REST_Response(array(
			'textures' => array(
				'id'      => $next,
				'message' => __('你帮我换了一件新衣服!', 'live-2d'),
			),
		), 200);
	}

	// =================== 内部工具方法 ===================

	/**
	 * 把前端传入的 "modelName-textureId" 拆开。textureId 缺省 0。
	 * modelName 不在 catalog 中的会在调用方触发 404,这里只做字符串切分,不做白名单。
	 */
	private function parse_id_pair($raw)
	{
		$raw = is_string($raw) ? trim($raw) : '';
		if ($raw === '') {
			return array('', 0);
		}
		// 模型名本身允许含 '-'(如 bilibili-live_22),所以从右往左切一次。
		// 仅当最右侧那段为纯数字时才认为是 textureId,否则把整个 $raw 当作 modelName。
		$pos = strrpos($raw, '-');
		if ($pos !== false) {
			$head = substr($raw, 0, $pos);
			$tail = substr($raw, $pos + 1);
			if (ctype_digit($tail)) {
				return array($head, intval($tail));
			}
		}
		return array($raw, 0);
	}

	private function find_catalog_item($modelName)
	{
		if (!is_string($modelName) || $modelName === '') {
			return null;
		}
		foreach (live2d_SDK::GetV1Catalog() as $item) {
			if ($item['name'] === $modelName) {
				return $item;
			}
		}
		return null;
	}

	/** 已解压到 model/{name}/ 的 catalog 条目 name 数组。与 GetModelMotions 的判定一致。 */
	private function list_downloaded_models()
	{
		$result = array();
		foreach (live2d_SDK::GetV1Catalog() as $item) {
			$dir = DOWNLOAD_DIR . sanitize_file_name($item['name']);
			if (is_dir($dir)) {
				$result[] = $item['name'];
			}
		}
		return $result;
	}

	/** 模型可用 textureId 数量(skins 或 textures.json 长度,默认 1)。 */
	private function texture_count($modelName)
	{
		$item = $this->find_catalog_item($modelName);
		if ($item === null) {
			return 1;
		}
		if (!empty($item['skins'])) {
			return count($item['skins']);
		}
		if (!empty($item['texturesJson'])) {
			$json = $this->read_json_file(dirname(__DIR__) . '/assets/v1/' . $modelName . '.textures.json');
			if (is_array($json) && !empty($json)) {
				return count($json);
			}
		}
		return 1;
	}

	private function pick_random_other(array $list, $current)
	{
		if (empty($list)) {
			return $current;
		}
		if (count($list) === 1) {
			return $list[0];
		}
		$candidates = array_values(array_filter($list, function ($v) use ($current) {
			return $v !== $current;
		}));
		return $candidates[array_rand($candidates)];
	}

	private function pick_next_in_list(array $list, $current)
	{
		if (empty($list)) {
			return $current;
		}
		$idx = array_search($current, $list, true);
		if ($idx === false) {
			return $list[0];
		}
		return $list[($idx + 1) % count($list)];
	}

	private function pick_random_other_int($current, $count)
	{
		if ($count <= 1) {
			return $current;
		}
		// 在 [0, count-1] 中随机选一个不等于 current 的;count >= 2 必有解。
		do {
			$candidate = wp_rand(0, $count - 1);
		} while ($candidate === $current);
		return $candidate;
	}

	/** 读 JSON 文件,容忍 UTF-8 BOM,失败返回 null。 */
	private function read_json_file($path)
	{
		if (!file_exists($path)) {
			return null;
		}
		$raw = file_get_contents($path);
		if ($raw === false) {
			return null;
		}
		if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
			$raw = substr($raw, 3);
		}
		$raw = trim($raw);
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			error_log('live2d_V1Api:[json_decode 失败] path=' . $path . ' err=' . json_last_error_msg());
			return null;
		}
		return $data;
	}

	/**
	 * 用 assets/v1/{name}.textures.json 的第 textureId 项替换 manifest.textures。
	 * textures.json 顶层既可能是 string[][](每项一组并排贴图),也可能是 string[](每项单张),
	 * 此处统一兜住:数组项 -> 直接当一整组贴图;字符串项 -> 包成单元素数组。
	 * 越界 / 文件缺失 / 解析失败 -> 不修改 manifest.textures(仍用 index.json 自带的)。
	 */
	private function apply_textures_json(array &$manifest, $modelName, $textureId)
	{
		$path = dirname(__DIR__) . '/assets/v1/' . $modelName . '.textures.json';
		$json = $this->read_json_file($path);
		if (!is_array($json) || empty($json)) {
			return;
		}
		if ($textureId < 0 || $textureId >= count($json)) {
			return;
		}
		$entry = $json[$textureId];
		if (is_string($entry)) {
			$manifest['textures'] = array($entry);
		} elseif (is_array($entry)) {
			$manifest['textures'] = array_values($entry);
		}
	}

	/**
	 * 把 V1 manifest 里所有相对路径字段重写为绝对 URL,
	 * 让前端 LAppModel.load 不依赖 modelHomeDir(避免 modelSettingPath 形如
	 * /wp-json/live2d/v1/model/get/?id=... 时切出来的 modelHomeDir 完全错位)。
	 *
	 * 涉及字段:model / textures[] / physics / pose /
	 *           expressions[].file / motions{group:[]}.{file,sound}
	 */
	private function rewrite_manifest_paths(array &$manifest, $baseUrl)
	{
		$prefix = function ($rel) use ($baseUrl) {
			if (!is_string($rel) || $rel === '') return $rel;
			// 已经是绝对 URL 就不动
			if (preg_match('#^(https?:)?//#i', $rel)) return $rel;
			// 去掉前导 ./ 与 /,统一接到 baseUrl 后
			$rel = ltrim($rel, '/');
			if (strpos($rel, './') === 0) $rel = substr($rel, 2);
			return $baseUrl . live2d_v1api_rawurlencode_path($rel);
		};

		if (isset($manifest['model'])) {
			$manifest['model'] = $prefix($manifest['model']);
		}
		if (isset($manifest['textures']) && is_array($manifest['textures'])) {
			$manifest['textures'] = array_map($prefix, $manifest['textures']);
		}
		if (isset($manifest['physics'])) {
			$manifest['physics'] = $prefix($manifest['physics']);
		}
		if (isset($manifest['pose'])) {
			$manifest['pose'] = $prefix($manifest['pose']);
		}
		if (isset($manifest['expressions']) && is_array($manifest['expressions'])) {
			foreach ($manifest['expressions'] as $i => $exp) {
				if (is_array($exp) && isset($exp['file'])) {
					$manifest['expressions'][$i]['file'] = $prefix($exp['file']);
				}
			}
		}
		if (isset($manifest['motions']) && is_array($manifest['motions'])) {
			foreach ($manifest['motions'] as $group => $motions) {
				if (!is_array($motions)) continue;
				foreach ($motions as $i => $m) {
					if (!is_array($m)) continue;
					if (isset($m['file']))  $manifest['motions'][$group][$i]['file']  = $prefix($m['file']);
					if (isset($m['sound'])) $manifest['motions'][$group][$i]['sound'] = $prefix($m['sound']);
				}
			}
		}
	}

	private function plugin_model_url()
	{
		// dirname(__DIR__) 指向插件根; plugin_dir_url 在符号链接 / mu-plugins 等
		// 边界场景下也能拿到正确的 URL,与 live2d_Shop 中保持一致。
		return rtrim(plugin_dir_url(dirname(__FILE__)) . 'model', '/');
	}
}

if (!function_exists('live2d_v1api_rawurlencode_path')) {
	/**
	 * rawurlencode 但保留 '/'(否则 motions/idle.mtn 会变成 motions%2Fidle.mtn,
	 * 让 nginx/Apache 找不到文件)。
	 */
	function live2d_v1api_rawurlencode_path($path)
	{
		$parts = explode('/', (string) $path);
		return implode('/', array_map('rawurlencode', $parts));
	}
}
