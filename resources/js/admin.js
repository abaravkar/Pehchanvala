document.querySelectorAll('[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!confirm(form.dataset.confirm))e.preventDefault();}));
const source=document.querySelector('[data-slug-source]'),target=document.querySelector('[data-slug-target]');
if(source&&target){let manual=Boolean(target.value);target.addEventListener('input',()=>manual=Boolean(target.value));source.addEventListener('input',()=>{if(!manual)target.value=source.value.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');});}
const type=document.querySelector('#property-form select[name=type]');
function updateFields(){document.querySelectorAll('[data-field-for]').forEach(el=>{const show=el.dataset.fieldFor.split(' ').includes(type.value);el.hidden=!show;el.querySelectorAll('input').forEach(input=>input.disabled=!show);});}
if(type){type.addEventListener('change',updateFields);updateFields();}
import 'leaflet/dist/leaflet.css';
import './location-picker';
