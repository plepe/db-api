const DBApiExt = require('./DBApiExt')
const leafletMap = require('./leafletMap')
const leafletImage = require('leaflet-image')
const async = {
  each: require('async/each')
}

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

  export (div, options, callback) {
    let divs = div.getElementsByTagName('map')

    async.each(divs,
      (div, done) => {
        let map = div.map
        leafletImage(map, (err, canvas) => {
          let img = document.createElement('img')
          let dimensions = map.getSize()
          img.width = dimensions.x
          img.height = dimensions.y
          img.src = canvas.toDataURL(div.getAttribute('data-image-type') || 'image/png', div.getAttribute('data-image-quality') || null)

          let attrs = div.attributes
          Array.prototype.forEach.call(attrs, (attr) => {
            img.setAttribute(attr.name, attr.value)
          })

          div.parentNode.replaceChild(img, div)

//          let divAttr = document.createElement('div')
//          divAttr.className = 'attribution'
//          divAttr.innerHTML = layers[options.preferredLayer].options.attribution
//          img.appendChild(divAttr)

          done()
        })
      },
      (err) => {
        callback(err)
      }
    )
  }
}

module.exports = DBApiExtLeaflet
