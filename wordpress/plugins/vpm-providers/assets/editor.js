(() => {
  const { createElement: el } = wp.element;
  const { useSelect, useDispatch } = wp.data;
  const { TextControl, TextareaControl, CheckboxControl } = wp.components;
  const Panel = wp.editor.PluginDocumentSettingPanel || wp.editPost.PluginDocumentSettingPanel;
  function ProviderFields() {
    const data = useSelect((select) => ({
      type: select('core/editor').getCurrentPostType(),
      meta: select('core/editor').getEditedPostAttribute('meta') || {},
      order: select('core/editor').getEditedPostAttribute('menu_order') || 0,
    }), []);
    const { editPost } = useDispatch('core/editor');
    if (data.type !== 'vpm_provider') return null;
    const update = (key, value) => editPost({ meta: { ...data.meta, [`_vpm_${key}`]: value } });
    const controls = Object.entries(vpmProviderFields).map(([key, field]) => {
      const props = { key, label: field.label, onChange: (value) => update(key, value) };
      if (field.type === 'boolean') return el(CheckboxControl, { ...props, checked: !!data.meta[`_vpm_${key}`] });
      props.value = data.meta[`_vpm_${key}`] || '';
      if (key === 'features') return el(TextareaControl, { ...props, rows: 5 });
      return el(TextControl, { ...props, type: key.endsWith('_url') ? 'url' : 'text' });
    });
    controls.push(el(TextControl, {
      key: 'order', label: 'Reihenfolge (kleinere Zahlen zuerst)', type: 'number', value: data.order,
      onChange: (value) => editPost({ menu_order: Number.parseInt(value, 10) || 0 }),
    }));
    return el(Panel, { name: 'vpm-provider-details', title: 'Anbieterangaben und Buchungslinks', initialOpen: true },
      el('p', null, 'Nur freigegebene Angaben verwenden. Keine Live-Abfrage von Preisen oder Verfügbarkeiten.'), ...controls);
  }
  wp.plugins.registerPlugin('vpm-provider-fields', { render: ProviderFields, icon: 'car' });
})();
