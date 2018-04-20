const DBApiExt = require('./DBApiExt')

class DBApiExtLeaflet extends DBApiExt {
  constructor (view, options={}) {
    super(view, options)

    view.on('showEntry', ev => {
      let divs = ev.dom.getElementsByTagName('map')

      for (var i = 0; i < divs.length; i++) {
        let div = divs[i]
        let mapOptions = JSON.parse(JSON.stringify(this.options))

        if (div.hasAttribute('options')) {
          let o = JSON.parse(div.getAttribute('options'))
          for (let k in o) {
            mapOptions[k] = o[k]
          }
        }
        console.log(mapOptions)

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
          center: [ ev.entry[mapOptions.latitudeField], ev.entry[mapOptions.longitudeField] ],
          zoom: mapOptions.zoom || 17,
          layers: defaultLayer
        })

        if (Object.keys(mapLayers).length > 1) {
          L.control.layers(mapLayers).addTo(map)
        }

        L.marker([ ev.entry[mapOptions.latitudeField], ev.entry[mapOptions.longitudeField] ]).addTo(map)
      }
    })
  }
}

module.exports = DBApiExtLeaflet
