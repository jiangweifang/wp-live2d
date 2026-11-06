/**
 *
 *  You can modify and use this source freely
 *  only for the development of application related Live2D.
 *
 *  (c) Live2D Inc. All rights reserved.
 */
export class MatrixStack{
    
    public reset(){
        this.depth = 0;
    }

    public loadIdentity(){
        for (var i = 0; i < 16; i++)
        {
            this.currentMatrix[i] = (i % 5 == 0) ? 1 : 0;
        }
    }

    public push(){
        let offset = this.depth * 16;
        let nextOffset = (this.depth + 1) * 16;
        
        if (this.matrixStack.length < nextOffset + 16)
        {
            this.matrixStack.length = nextOffset + 16;
        }
    
        for (let i = 0; i < 16; i++)
        {
            this.matrixStack[nextOffset + i] = this.currentMatrix[i];
        }
    
        this.depth++;
    }

    public pop(){
        this.depth--;
        if (this.depth < 0)
        {
            console.log("Invalid matrix stack. 无效的矩阵堆栈")
            this.depth = 0;
        }
    
        let offset = this.depth * 16;
        for (let i = 0; i < 16; i++)
        {
            this.currentMatrix[i] = this.matrixStack[offset + i];
        }
    }

    public getMatrix(){
        return this.currentMatrix;
    }

    public multMatrix(matNew){
        let i, j, k;

        for (i = 0; i < 16; i++)
        {
            this.tmp[i] = 0;
        }
    
        for (i = 0; i < 4; i++)
        {
            for (j = 0; j < 4; j++)
            {
                for (k = 0; k < 4; k++)
                {
                    this.tmp[i + j * 4] += this.currentMatrix[i + k * 4] * matNew[k + j * 4];
                }
            }
        }
        for (i = 0; i < 16; i++)
        {
            this.currentMatrix[i] = this.tmp[i];
        }
    }

    depth = 0;

    matrixStack = [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1];

    currentMatrix = [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1];

    tmp = new Array(16);
}

