(() => {
  document.addEventListener('click', event => {
    const button=event.target.closest('.vpm-brand-pick,.vpm-brand-clear');if(!button)return;
    const box=button.closest('.vpm-brand-media'),input=box.querySelector('input'),preview=box.querySelector('img');
    if(button.classList.contains('vpm-brand-clear')){input.value='0';preview.removeAttribute('src');preview.hidden=true;return;}
    if(!window.wp?.media)return;const frame=wp.media({title:'Logo auswählen',button:{text:'Logo verwenden'},library:{type:'image'},multiple:false});
    frame.on('select',()=>{const image=frame.state().get('selection').first().toJSON();input.value=String(image.id);preview.src=image.url;preview.hidden=false;});frame.open();
  });
})();
