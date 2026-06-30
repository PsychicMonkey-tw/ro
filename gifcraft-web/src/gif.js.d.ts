declare module 'gif.js' {
  interface GIFOptions {
    workers?: number;
    quality?: number;
    width?: number;
    height?: number;
    workerScript?: string;
  }

  interface FrameOptions {
    delay?: number;
    copy?: boolean;
  }

  export default class GIF {
    constructor(options: GIFOptions);
    addFrame(element: HTMLCanvasElement, options?: FrameOptions): void;
    on(event: 'finished', handler: (blob: Blob) => void): void;
    on(event: 'progress', handler: (p: number) => void): void;
    on(event: 'error', handler: (e: Error) => void): void;
    render(): void;
  }
}
