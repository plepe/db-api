const DBApiExt = require('./DBApiExt')
const leafletMap = require('./leafletMap')

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

        leafletMap(div, mapOptions, ev.entry)
      }
    })
  }
}

module.exports = DBApiExtLeaflet
