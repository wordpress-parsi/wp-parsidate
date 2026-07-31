interface CalendarBlockAttributes {
  title: string;
  postType: string;
  theme: string;
}

interface CalendarBlockProps {
  attributes: CalendarBlockAttributes;
  setAttributes: (attributes: Partial<CalendarBlockAttributes>) => void;
}

const el = window.wp.element.createElement;
const { Fragment } = window.wp.element;
const __ = window.wp.i18n.__;
const { InspectorControls, useBlockProps } = window.wp.blockEditor;
const { PanelBody, TextControl, SelectControl } = window.wp.components;
const ServerSideRender = window.wp.serverSideRender;
const { registerBlockType } = window.wp.blocks;

const blockData = window.wppCalendarBlockData || { postTypes: [], convPermalinks: false };

const postTypes: WPParsidateArchivePostType[] = blockData.postTypes;
const convPermalinks: boolean = blockData.convPermalinks;

const themeOptions: Array<{ label: string; value: string }> = [
  { label: '---', value: 'none' },
  { label: __('Simple', 'wp-parsidate'), value: 'simple' },
  { label: __('Light', 'wp-parsidate'), value: 'light' },
  { label: __('Dark', 'wp-parsidate'), value: 'dark' },
];

function calendarPlaceholder(attributes: CalendarBlockAttributes): unknown {
  return el(
    'div',
    { className: 'wp-block-wp-parsidate-calendar__placeholder' },
    el(
      'div',
      { className: 'wp-block-wp-parsidate-calendar__icon' },
      el(
        'svg',
        {
          width: 48,
          height: 48,
          viewBox: '0 0 24 24',
          fill: 'none',
          xmlns: 'http://www.w3.org/2000/svg',
        },
        el('path', {
          d: 'M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z',
          fill: 'currentColor',
        })
      )
    ),
    el('h3', null, attributes.title || __('Calendar', 'wp-parsidate')),
    convPermalinks
      ? null
      : el(
          'p',
          { className: 'wp-block-wp-parsidate-calendar__notice' },
          __(
            'To use this block, activate the "Fix permalinks dates" option in plugin settings.',
            'wp-parsidate'
          )
        )
  );
}

registerBlockType('wp-parsidate/calendar', {
  edit(props: CalendarBlockProps): unknown {
    const { attributes, setAttributes } = props;
    const blockProps = useBlockProps();

    return el(
      Fragment,
      null,
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __('Settings', 'wp-parsidate'), initialOpen: true },
          el(TextControl, {
            label: __('Title', 'wp-parsidate'),
            value: attributes.title,
            onChange: (value: string) => setAttributes({ title: value }),
          }),
          el(SelectControl, {
            label: __('Post type', 'wp-parsidate'),
            value: attributes.postType,
            options: postTypes,
            onChange: (value: string) => setAttributes({ postType: value }),
          }),
          el(SelectControl, {
            label: __('Theme', 'wp-parsidate'),
            value: attributes.theme,
            options: themeOptions,
            onChange: (value: string) => setAttributes({ theme: value }),
          })
        )
      ),
      el(
        'div',
        blockProps,
        el(ServerSideRender, {
          block: 'wp-parsidate/calendar',
          attributes: attributes,
          EmptyResponsePlaceholder: () => calendarPlaceholder(attributes),
        })
      )
    );
  },
  save(): null {
    return null;
  },
});

export {};
