(function (blocks, element, editor, components, ServerSideRender) {
  ['form', 'airport-selector', 'newsletter'].forEach(function (kind) {
    var names = {form: 'VPM Buchungsanfrage', 'airport-selector': 'VPM Flughafenauswahl', newsletter: 'VPM Newsletter-Anmeldung'};
    blocks.registerBlockType('vpm-booking/' + kind, {
      title: names[kind], category: 'widgets', icon: 'airplane',
      attributes: Object.assign({airport: {type: 'string', default: ''}, locale: {type: 'string', default: 'auto'}}, kind === 'form' ? {airport_mode: {type: 'string', default: ''}, flow: {type: 'string', default: 'inline'}, service:{type:'string',default:'any'}, time_basis:{type:'string',default:'flight'}} : {}),
      edit: function (props) {
        return element.createElement(element.Fragment, null,
          element.createElement(editor.InspectorControls, null,
            element.createElement(components.PanelBody, {title: 'VPM Einstellungen'},
              kind === 'form' ? element.createElement(components.TextControl, {label: 'Lokaler Flughafen (IATA, z. B. STR; leer = Auswahl)', value: props.attributes.airport || '', onChange: function (v) {props.setAttributes({airport: v.toUpperCase()});}}) : null,
              kind === 'form' ? element.createElement(components.SelectControl, {label: 'Flughafenauswahl', value: props.attributes.airport_mode || '', options: [{label: 'Automatisch: IATA gesetzt = fest', value: ''}, {label: 'Flughafen fest vorgeben', value: 'fixed'}, {label: 'Flughafen auswählbar', value: 'select'}], onChange: function(v) {props.setAttributes({airport_mode:v});}}) : null,
              kind === 'form' ? element.createElement(components.SelectControl, {label:'Buchungsablauf',value:props.attributes.flow||'inline',options:[{label:'Bestehendes Anfrageformular',value:'inline'},{label:'Mehrstufiger Demo-Ablauf',value:'steps'}],onChange:function(v){props.setAttributes({flow:v});}}) : null,
              kind === 'form' ? element.createElement(components.SelectControl,{label:'Service vorauswählen (Kunde kann ändern)',value:props.attributes.service||'any',options:[{label:'Alle',value:'any'},{label:'Valet',value:'valet'},{label:'Shuttle',value:'shuttle'},{label:'Nur Parken',value:'parking'}],onChange:function(v){props.setAttributes({service:v});}}):null,
              kind === 'form' ? element.createElement(components.SelectControl,{label:'Zeiteingabe',value:props.attributes.time_basis||'flight',options:[{label:'Flugzeiten (−3 Stunden / +30 Minuten)',value:'flight'},{label:'Direkte Parkplatzzeiten',value:'parking'}],onChange:function(v){props.setAttributes({time_basis:v});}}):null,
              element.createElement(components.SelectControl, {label: 'Sprache', value: props.attributes.locale, options: [{label: 'Automatisch', value: 'auto'}, {label: 'Deutsch', value: 'de'}, {label: 'English', value: 'en'}], onChange: function (v) {props.setAttributes({locale: v});}}))),
          element.createElement('div', editor.useBlockProps(), element.createElement(ServerSideRender, {block: 'vpm-booking/' + kind, attributes: props.attributes}))) ;
      },
      save: function () {return null;}
    });
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender.default || window.wp.serverSideRender);
