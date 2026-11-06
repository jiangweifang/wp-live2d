import { cachedFetchBytes } from './DbCache';

export const IDB_V1_PREFIX = 'idb-v1://';

export async function makeIdbObjectUrl(path: string): Promise<string> {
    return URL.createObjectURL(new Blob([await cachedFetchBytes(path)]));
}
