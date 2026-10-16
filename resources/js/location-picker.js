import L from 'leaflet';
import '../css/location-picker.css';
const picker=document.querySelector('[data-location-picker]');
if(picker){
    const latitude=picker.querySelector('[name=latitude]'),longitude=picker.querySelector('[name=longitude]');
    const address=picker.querySelector('[name=map]'),search=picker.querySelector('#location-search');
    const results=picker.querySelector('#location-results'),status=picker.querySelector('.location-status');
    const searchButton=picker.querySelector('[data-location-search]'),googleLink=picker.querySelector('[data-google-pin]');
    const form=picker.closest('form');
    const validPair=()=>latitude.value!==''&&longitude.value!==''&&Number.isFinite(Number(latitude.value))&&Number.isFinite(Number(longitude.value))&&Math.abs(Number(latitude.value))<=90&&Math.abs(Number(longitude.value))<=180;
    const initial=validPair()?[Number(latitude.value),Number(longitude.value)]:null;
    const map=L.map('property-location-map',{scrollWheelZoom:false}).setView(initial||[18.5204,73.8567],initial?16:12);
    const tiles=L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}).addTo(map);
    tiles.on('tileerror',()=>{status.textContent='Map tiles could not load. Search or enter coordinates to select a location.';});
    const icon=L.divIcon({className:'property-pin',html:'<span aria-hidden="true"></span>',iconSize:[30,40],iconAnchor:[15,40]});
    let marker=null,controller=null;
    function place(lat,lng,{center=false,label=null,announce=true}={}){
        if(!Number.isFinite(lat)||!Number.isFinite(lng)||Math.abs(lat)>90||Math.abs(lng)>180)return;
        latitude.value=lat.toFixed(7);longitude.value=lng.toFixed(7);
        if(!marker){marker=L.marker([lat,lng],{icon,draggable:true,keyboard:true,title:'Property location pin. Drag to adjust.',autoPan:true}).addTo(map);marker.on('dragend',()=>{const p=marker.getLatLng();place(p.lat,p.lng);});}
        else marker.setLatLng([lat,lng]);
        if(label)address.value=label;
        if(!address.value.trim())address.value=`${lat.toFixed(7)}, ${lng.toFixed(7)}`;
        googleLink.href=`https://www.google.com/maps/search/?api=1&query=${lat.toFixed(7)},${lng.toFixed(7)}`;googleLink.hidden=false;
        if(center)map.setView([lat,lng],16);
        if(announce)status.textContent=`Pin selected at ${lat.toFixed(6)}, ${lng.toFixed(6)}. Save the property to keep it.`;
        latitude.setCustomValidity('');longitude.setCustomValidity('');
    }
    if(initial)place(...initial,{announce:false});else status.textContent='No exact pin saved yet. Search or click the map to select one.';
    map.on('click',e=>place(e.latlng.lat,e.latlng.lng));
    for(const input of [latitude,longitude])input.addEventListener('change',()=>{if(validPair())place(Number(latitude.value),Number(longitude.value),{center:true});else{if(marker){marker.remove();marker=null;}googleLink.hidden=true;status.textContent='Enter both valid coordinates or select a point on the map.';}});
    picker.querySelector('[data-clear-pin]').addEventListener('click',()=>{if(marker){marker.remove();marker=null;}latitude.value='';longitude.value='';googleLink.hidden=true;latitude.setCustomValidity('');longitude.setCustomValidity('');status.textContent='Pin cleared. The address will still be saved unless you change it.';});
    async function find(){
        const q=search.value.trim();if(q.length<3){status.textContent='Enter at least 3 characters to search.';search.focus();return;}
        controller?.abort();controller=new AbortController();searchButton.disabled=true;results.replaceChildren();status.textContent='Searching Pune locations…';
        try{
            const response=await fetch(`/admin/locations/search?q=${encodeURIComponent(q)}`,{headers:{Accept:'application/json'},signal:controller.signal});
            if(!response.ok)throw new Error();const data=await response.json();
            if(!data.results.length){status.textContent='No matching location found. Try a nearby landmark, or place the pin on the map.';return;}
            for(const result of data.results){const button=document.createElement('button');button.type='button';button.className='location-result';button.textContent=result.label;button.addEventListener('click',()=>{place(Number(result.latitude),Number(result.longitude),{center:true,label:result.label});search.value=result.label;results.replaceChildren();});results.append(button);}
            status.textContent=`${data.results.length} locations found. Choose one below.`;
        }catch(error){if(error.name!=='AbortError')status.textContent='Search is unavailable right now. You can still click the map or enter coordinates.';}
        finally{searchButton.disabled=false;}
    }
    searchButton.addEventListener('click',find);search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();find();}});
    form.addEventListener('submit',e=>{if((latitude.value!==''||longitude.value!=='')&&!validPair()){e.preventDefault();latitude.setCustomValidity('Enter a valid latitude and longitude pair, or clear the pin.');latitude.reportValidity();}});
}
