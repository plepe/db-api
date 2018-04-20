const DBApiExt = require('./DBApiExt')

class DBApiExtDummy extends DBApiExt {
  constructor (view, options={}) {
    super(view, options)

    view.on('showEntry', ev => {
      let div = document.createElement('div')
      div.innerHTML = options.text || 'DUMMY'
      ev.dom.appendChild(div)
    })
  }
}

module.exports = DBApiExtDummy
