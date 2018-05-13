const DBApiExt = require('./DBApiExt')
const async = {
  each: require('async/each')
}

class DBApiExtImageExport extends DBApiExt {
  export (div, options, callback) {
    let imgs = div.getElementsByTagName('img')

    async.each(imgs,
      (img, done) => {
        if (img.width) {
          _do.call(this)
        } else {
          img.onload = _do.bind(this)
        }

        function _do () {
          // add 'width' and 'height'
          let factorWidth = img.getAttribute('max-width') / img.width || 1
          let factorHeight = img.getAttribute('max-height') / img.height || 1
          let factor = Math.min(factorWidth, factorHeight)

          //console.log(img.width, img.height, factor)

          let w = img.width * factor
          let h = img.height * factor

          img.setAttribute('width', w)
          img.setAttribute('height', h)
          done()
        }
      },
      (err) => {
        callback(err)
      }
    )
  }
}

module.exports = DBApiExtImageExport
