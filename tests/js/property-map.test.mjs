import test from 'node:test';
import assert from 'node:assert/strict';
import {propertyMapHTML} from '../../resources/js/property-map.js';
test('saved coordinates take priority over an approximate address',()=>{
    const html=propertyMapHTML({area:'Baner',map:'Old approximate area',latitude:18.5590001,longitude:73.7868002});
    assert.ok(html.includes('query=18.5590001%2C73.7868002'));
    assert.ok(html.includes('q=18.5590001%2C73.7868002&z=17'));
    assert.ok(!html.includes('Old%20approximate'));
});
test('legacy listings retain address lookup without an invented pin',()=>{
    const html=propertyMapHTML({area:'Baner',map:'Baner, Pune',latitude:null,longitude:null});
    assert.ok(html.includes('q=Baner%2C%20Pune&z=14'));
    assert.ok(html.includes('Neighbourhood reference only'));
});
