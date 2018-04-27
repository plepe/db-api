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

        let data = ev.entries[i]
        leafletMap(td, options, data)
      }
    })
  }
}

module.exports = DBApiExtModulekitFormLeaflet
