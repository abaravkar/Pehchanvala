<div class="location-picker" data-location-picker>
    <label for="location-search">Search for a property location</label>
    <div class="location-search-row"><input id="location-search" type="search" value="{{ old('map',$item->map) }}" placeholder="Building, landmark or area in Pune" maxlength="180" autocomplete="off" aria-controls="location-results"><button class="button" type="button" data-location-search>Search ↗</button></div>
    <p class="help">Search and choose a result, then click the map or drag the pin to the property entrance.</p>
    <div id="location-results" class="location-results" aria-label="Location search results"></div>
    <p class="location-status help" role="status" aria-live="polite"></p>
    <div id="property-location-map" class="location-map" tabindex="0" aria-label="Property pin picker. Use search or coordinate fields as a keyboard alternative."></div>
    <div class="location-coordinate-fields"><x-field name="latitude" label="Pin latitude" type="number" step="0.0000001" min="-90" max="90" :value="$item->latitude"/><x-field name="longitude" label="Pin longitude" type="number" step="0.0000001" min="-180" max="180" :value="$item->longitude"/><button type="button" class="text-button" data-clear-pin>Clear pin</button></div>
    <x-field name="map" label="Selected address / location" :value="$item->map" required maxlength="500"/>
    <p class="help">Picker: © OpenStreetMap contributors. Search: Photon / OpenStreetMap. The website’s Google Maps view uses the saved coordinates. <a data-google-pin target="_blank" rel="noopener" hidden>Check pin in Google Maps ↗</a></p>
</div>
