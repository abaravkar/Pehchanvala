export function propertyMapHTML(p){
    const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const pinned=p.latitude!=null&&p.longitude!=null&&Number.isFinite(Number(p.latitude))&&Number.isFinite(Number(p.longitude));
    const location=pinned?`${Number(p.latitude)},${Number(p.longitude)}`:(p.map||`${p.area}, Pune`);
    const query=encodeURIComponent(location);
    return `<iframe class="map-frame" title="${escape(p.area)} ${pinned?'property pin':'neighbourhood map, approximate location'}" src="https://maps.google.com/maps?q=${query}&z=${pinned?17:14}&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><p class="map-caption">${pinned?'Property pin selected by the agency · Confirm the entrance before travel.':'Neighbourhood reference only · Exact property location to be confirmed.'} <a href="https://www.google.com/maps/search/?api=1&query=${query}" target="_blank" rel="noopener">Open Google Maps ↗</a></p>`;
}
