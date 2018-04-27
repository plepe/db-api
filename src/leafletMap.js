function leafletMap (div, mapOptions, entry, callback) {
  let result = {}

  let layers
  if ('layers' in mapOptions) {
    layers = mapOptions.layers
  } else {
    layers = {
      'OpenStreetMap Mapnik': {
          url: '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }
    }
  }

  let mapLayers = {}
  for (var k in layers) {
    mapLayers[k] = L.tileLayer(layers[k].url, layers[k])
  }

  let defaultLayer
  if (mapOptions.layer) {
    defaultLayer = mapLayers[mapOptions.layer]
  } else {
    defaultLayer = mapLayers[Object.keys(mapLayers)[0]]
  }

  var map = L.map(div, {
    center: [ entry[mapOptions.latitudeField], entry[mapOptions.longitudeField] ],
    zoom: mapOptions.zoom || 17,
    layers: defaultLayer
  })

  if (Object.keys(mapLayers).length > 1) {
    L.control.layers(mapLayers).addTo(map)
  }

  result.marker = L.marker([ entry[mapOptions.latitudeField], entry[mapOptions.longitudeField] ], mapOptions.markerOptions).addTo(map)

  if (callback) {
    callback(null, result)
  }
}

module.exports = leafletMap
