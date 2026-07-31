interface WPParsidateArchivePostType {
    label: string;
    value: string;
}

interface WPParsidateArchiveBlockData {
  postTypes: WPParsidateArchivePostType[];
  convPermalinks: boolean;
}

interface WPParsidateCalendarBlockData {
  postTypes: WPParsidateArchivePostType[];
  convPermalinks: boolean;
}

interface Window {
  wp: {
    element: {
      Fragment: unknown;
      createElement: (
        type: unknown,
        props?: Record<string, unknown> | null,
        ...children: unknown[]
      ) => unknown;
    };
    i18n: {
      __: (text: string, domain?: string) => string;
    };
    blockEditor: {
      InspectorControls: unknown;
      useBlockProps: (props?: Record<string, unknown>) => Record<string, unknown>;
    };
    components: {
      PanelBody: unknown;
      TextControl: unknown;
      SelectControl: unknown;
      ToggleControl: unknown;
    };
    serverSideRender: unknown;
    blocks: {
      registerBlockType: (name: string, settings: Record<string, unknown>) => void;
    };
  };
  wppArchiveBlockData?: WPParsidateArchiveBlockData;
  wppCalendarBlockData?: WPParsidateCalendarBlockData;
}
