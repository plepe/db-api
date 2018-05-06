const DBApiExt = require('./DBApiExt')
const leafletMap = require('./leafletMap')

class DBApiExtModulekitFormLeaflet extends DBApiExt {
  constructor (view, options={}) {
    super(view, options)

    view.on('show', ev => {
      for (let i in ev.form.element.elements) {
        let element = ev.form.element.elements[i]
        let tr = document.createElement('tr')
        let td = document.createElement('td')
        tr.appendChild(td)
        td.setAttribute('colspan', 2)
        td.style = 'height: 300px;'

        element.dom_table.insertBefore(tr, element.dom_table.firstChild)

        let latField = element.elements[options.latitudeField]
        let lonField = element.elements[options.longitudeField]

        let data = ev.entries[i]
        options.markerOptions = {
          draggable: !latField.def.disabled && !lonField.def.disabled
        }
        leafletMap(td, options, data, (err, result) => {
          result.marker.on('dragend', e => {
            let pos = result.marker.getLatLng()
            let upd = {}
            upd[options.latitudeField] = pos.lat.toFixed(6)
            upd[options.longitudeField] = pos.lng.toFixed(6)

            element.set_data(upd)
          })
        })
      }
    })
  }
}

module.exports = DBApiExtModulekitFormLeaflet
