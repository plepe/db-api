const DBApiExt = require('./DBApiExt')

class DBApiExtLeaflet extends DBApiExt {
  constructor (view, options) {
    super(view, options)

    view.on('showEntry', ev => {
      let divs = ev.dom.getElementsByTagName('map')
      if (divs.length === 0) {
        return
      }

      var map = L.map(divs[0], {
        center: [ ev.entry[this.options.latitudeField], ev.entry[this.options.longitudeField] ],
        zoom: this.options.zoom || 17
      })

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      L.marker([ ev.entry[this.options.latitudeField], ev.entry[this.options.longitudeField] ]).addTo(map)
    })
  }
}

module.exports = DBApiExtLeaflet
