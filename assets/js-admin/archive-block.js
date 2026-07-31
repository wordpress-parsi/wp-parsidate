/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**********************************************!*\
  !*** ./assets/js-admin-src/archive-block.ts ***!
  \**********************************************/

const el = window.wp.element.createElement;
const { Fragment } = window.wp.element;
const __ = window.wp.i18n.__;
const { InspectorControls, useBlockProps } = window.wp.blockEditor;
const { PanelBody, TextControl, SelectControl, ToggleControl } = window.wp.components;
const ServerSideRender = window.wp.serverSideRender;
const { registerBlockType } = window.wp.blocks;
const blockData = window.wppArchiveBlockData || { postTypes: [], convPermalinks: false };
const postTypes = blockData.postTypes;
const convPermalinks = blockData.convPermalinks;
const typeOptions = [
    { label: __('Yearly', 'wp-parsidate'), value: 'yearly' },
    { label: __('Monthly', 'wp-parsidate'), value: 'monthly' },
    { label: __('Daily', 'wp-parsidate'), value: 'daily' },
];
function archiveTypeLabel(type) {
    return type === 'yearly'
        ? __('Yearly archive', 'wp-parsidate')
        : type === 'daily'
            ? __('Daily archive', 'wp-parsidate')
            : __('Monthly archive', 'wp-parsidate');
}
function archivePlaceholder(attributes) {
    return el('div', { className: 'wp-block-wp-parsidate-archive__placeholder' }, el('div', { className: 'wp-block-wp-parsidate-archive__icon' }, el('svg', {
        width: 48,
        height: 48,
        viewBox: '0 0 24 24',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg',
    }, el('path', {
        d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z',
        fill: 'currentColor',
    }), el('path', {
        d: 'M7 12h2v2H7zm0-4h2v2H7zm4 4h6v2h-6zm0-4h6v2h-6z',
        fill: 'currentColor',
    }))), el('h3', null, attributes.title || __('Archive', 'wp-parsidate')), el('p', { className: 'wp-block-wp-parsidate-archive__type' }, archiveTypeLabel(attributes.type)), convPermalinks
        ? null
        : el('p', { className: 'wp-block-wp-parsidate-archive__notice' }, __('To use this block, activate the "Fix permalinks dates" option in plugin settings.', 'wp-parsidate')));
}
registerBlockType('wp-parsidate/archive', {
    edit(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();
        return el(Fragment, null, el(InspectorControls, null, el(PanelBody, { title: __('Settings', 'wp-parsidate'), initialOpen: true }, el(TextControl, {
            label: __('Title', 'wp-parsidate'),
            value: attributes.title,
            onChange: (value) => setAttributes({ title: value }),
        }), el(SelectControl, {
            label: __('Post type', 'wp-parsidate'),
            value: attributes.postType,
            options: postTypes,
            onChange: (value) => setAttributes({ postType: value }),
        }), el(SelectControl, {
            label: __('How to display', 'wp-parsidate'),
            value: attributes.type,
            options: typeOptions,
            onChange: (value) => setAttributes({ type: value }),
        }), el(ToggleControl, {
            label: __('Display as dropdown', 'wp-parsidate'),
            checked: attributes.displaySelect,
            onChange: (value) => setAttributes({ displaySelect: value }),
        }), el(ToggleControl, {
            label: __('Show post counts', 'wp-parsidate'),
            checked: attributes.displayCount,
            onChange: (value) => setAttributes({ displayCount: value }),
        }))), el('div', blockProps, el(ServerSideRender, {
            block: 'wp-parsidate/archive',
            attributes: attributes,
            EmptyResponsePlaceholder: () => archivePlaceholder(attributes),
        })));
    },
    save() {
        return null;
    },
});

/******/ })()
;