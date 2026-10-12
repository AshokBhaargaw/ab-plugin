(function (blocks, element, blockEditor, components, i18n, serverSideRender) {
  var el = element.createElement;
  var __ = i18n.__;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var ToggleControl = components.ToggleControl;
  var ServerSideRender = serverSideRender || wp.serverSideRender || components.ServerSideRender;

  registerBlockType('ab-addon/weather', {
    title: __('AB Weather', 'ab-addon'),
    description: __('Display live weather conditions and multi-day forecast for Ramdevra, Rajasthan.', 'ab-addon'),
    icon: 'cloud',
    category: 'ab-addon',

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      var inspectorControls = el(
        InspectorControls,
        { key: 'inspector' },
        el(
          PanelBody,
          { title: __('Display Settings', 'ab-addon'), initialOpen: true },
          el(SelectControl, {
            label: __('Display Mode', 'ab-addon'),
            value: attributes.mode,
            options: [
              { label: __('Full Weather Station & Forecast', 'ab-addon'), value: 'full' },
              { label: __('Compact Current Weather Card', 'ab-addon'), value: 'compact' },
              { label: __('Forecast Only', 'ab-addon'), value: 'forecast' }
            ],
            onChange: function (val) {
              setAttributes({ mode: val });
            }
          }),
          el(TextControl, {
            label: __('Custom Card Title', 'ab-addon'),
            value: attributes.title,
            placeholder: __('Ramdevra, Rajasthan, India', 'ab-addon'),
            onChange: function (val) {
              setAttributes({ title: val });
            }
          }),
          attributes.mode !== 'compact' &&
            el(RangeControl, {
              label: __('Forecast Days', 'ab-addon'),
              value: attributes.forecastDays,
              min: 1,
              max: 7,
              onChange: function (val) {
                setAttributes({ forecastDays: val });
              }
            })
        ),
        el(
          PanelBody,
          { title: __('Visible Fields', 'ab-addon'), initialOpen: false },
          el(ToggleControl, {
            label: __('Feels-Like Temperature', 'ab-addon'),
            checked: attributes.showFeelsLike,
            onChange: function (val) {
              setAttributes({ showFeelsLike: val });
            }
          }),
          el(ToggleControl, {
            label: __('Today High / Low', 'ab-addon'),
            checked: attributes.showHighLow,
            onChange: function (val) {
              setAttributes({ showHighLow: val });
            }
          }),
          el(ToggleControl, {
            label: __('Humidity', 'ab-addon'),
            checked: attributes.showHumidity,
            onChange: function (val) {
              setAttributes({ showHumidity: val });
            }
          }),
          el(ToggleControl, {
            label: __('Wind Speed', 'ab-addon'),
            checked: attributes.showWind,
            onChange: function (val) {
              setAttributes({ showWind: val });
            }
          }),
          el(ToggleControl, {
            label: __('Rain Probability', 'ab-addon'),
            checked: attributes.showRainProb,
            onChange: function (val) {
              setAttributes({ showRainProb: val });
            }
          }),
          el(ToggleControl, {
            label: __('Sunrise & Sunset Times', 'ab-addon'),
            checked: attributes.showSunTimes,
            onChange: function (val) {
              setAttributes({ showSunTimes: val });
            }
          }),
          el(ToggleControl, {
            label: __('Last Updated Time (IST)', 'ab-addon'),
            checked: attributes.showUpdatedTime,
            onChange: function (val) {
              setAttributes({ showUpdatedTime: val });
            }
          })
        ),
        el(
          PanelBody,
          { title: __('Travel Taxi CTA', 'ab-addon'), initialOpen: false },
          el(ToggleControl, {
            label: __('Show Taxi Booking Callout', 'ab-addon'),
            checked: attributes.showCta,
            onChange: function (val) {
              setAttributes({ showCta: val });
            }
          }),
          attributes.showCta &&
            el(TextControl, {
              label: __('Callout Text', 'ab-addon'),
              value: attributes.ctaText,
              placeholder: __('Planning a trip to Ramdevra? Book your taxi.', 'ab-addon'),
              onChange: function (val) {
                setAttributes({ ctaText: val });
              }
            }),
          attributes.showCta &&
            el(TextControl, {
              label: __('Booking URL', 'ab-addon'),
              value: attributes.ctaUrl,
              placeholder: 'https://ramdevraa.in/taxi-booking/',
              onChange: function (val) {
                setAttributes({ ctaUrl: val });
              }
            })
        )
      );

      // Editor canvas preview: use ServerSideRender if present, else fallback preview box.
      var preview = ServerSideRender
        ? el(ServerSideRender, {
            block: 'ab-addon/weather',
            attributes: attributes
          })
        : el(
            'div',
            { className: 'ab-weather-editor-preview' },
            el('div', { className: 'ab-weather-preview-header' },
              el('span', { className: 'dashicons dashicons-cloud' }),
              el('strong', null, attributes.title || 'Ramdevra, Rajasthan, India (Weather Preview)'),
              el('span', { className: 'ab-weather-preview-badge' }, attributes.mode.toUpperCase())
            ),
            el('p', { className: 'ab-weather-preview-desc' },
              __('Live weather will be rendered using the active weather provider in the page preview.', 'ab-addon')
            )
          );

      return el('div', { className: 'ab-weather-block-wrapper' }, inspectorControls, preview);
    },

    save: function () {
      // Dynamic block rendered via PHP render_callback.
      return null;
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor || window.wp.editor,
  window.wp.components,
  window.wp.i18n,
  window.wp.serverSideRender
);
