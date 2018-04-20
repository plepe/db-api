const DBApiExt = require('./DBApiExt')

class DBApiExtLeaflet extends DBApiExt {
  constructor (view, options={}) {
    super(view, options)

    view.on('showEntry', ev => {
      let divs = ev.dom.getElementsByTagName('map')

      for (var i = 0; i < divs.length; i++) {
        let div = divs[i]

        let layers
        if ('layers' in options) {
          layers = options.layers
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

        var defaultLayer = mapLayers[Object.keys(mapLayers)[0]]

        var map = L.map(div, {
          center: [ ev.entry[this.options.latitudeField], ev.entry[this.options.longitudeField] ],
          zoom: this.options.zoom || 17,
          layers: defaultLayer
        })

        if (Object.keys(mapLayers).length > 1) {
          L.control.layers(mapLayers).addTo(map)
        }

        L.marker([ ev.entry[this.options.latitudeField], ev.entry[this.options.longitudeField] ]).addTo(map)
      }
    })
  }
}

module.exports = DBApiExtLeaflet
