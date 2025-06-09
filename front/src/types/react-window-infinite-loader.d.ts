declare module 'react-window-infinite-loader' {
  import { ComponentType } from 'react';

  interface InfiniteLoaderProps {
    isItemLoaded: (index: number) => boolean;
    loadMoreItems: (startIndex: number, stopIndex: number) => Promise<void> | void;
    itemCount: number;
    children: (props: {
      onItemsRendered: (props: {
        visibleStartIndex: number;
        visibleStopIndex: number;
        overscanStartIndex: number;
        overscanStopIndex: number;
      }) => void;
      ref: (ref: any) => void;
    }) => React.ReactNode;
  }

  const InfiniteLoader: ComponentType<InfiniteLoaderProps>;
  export default InfiniteLoader;
} 