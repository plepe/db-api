const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')
const asyncForEach = require('async/eachOf')

class DBApiViewTwig extends DBApiView {
  constructor (dbApi, def, options) {
    super(dbApi, def, options)

    this.twig = this.options.twig
    this.twig.extendFilter('dbApiGet', function (value, args) {
      let result = dbApi.getCached(args[0], value, global._dbApiViewTwigCacheCallback)
      global._dbApiViewTwigCacheCallback = null

      if (typeof result === 'undefined') {
        global._dbApiViewTwigNeedReload = true
      }
      return result
    })
    this.template = this.twig.twig({
      data: def
    })
  }

  render (data, callback) {
    let result

    global._dbApiViewTwigNeedReload = false
    global._dbApiViewTwigCacheCallback = () => {
      callback(null, this.template.render(data))
    }

    result = this.template.render(data)

    if (!global._dbApiViewTwigNeedReload) {
      callback(null, result)
    }
  }

  show (dom, options={}, start=0, next=null, divMore=null) {
    options.step = 'step' in options ? options.step : 25

    if (options.step !== 0) {
      this.query.limit = options.step
    }
    if (options.step === 0) {
      // nothing
    } else if (start === 0 && options.step) {
      this.query.offset = start
      this.query.limit = this.query.limit + 1
    } else {
      this.query.offset = start + 1
    }

    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      if (next) {
        result = [ next ].concat(result)
      }
      if (options.step !== 0 && result.length > options.step) {
        next = result.pop()
      } else {
        next = null
      }

      let renderedResult = []

      if (start === 0) {
        emptyElement(dom)
      }

      asyncForEach(result,
        (entry, index, callback) => {
          let div = document.createElement('div')
          dom.appendChild(div)

          let data = {
            entry: entry
          }

          this.render(data, (err, r) => {
            div.innerHTML = r
            renderedResult[index] = r
            callback()
          })
        },
        () => {
          let showMoreFunction
          if (divMore) {
            dom.removeChild(divMore)
          }
          if (next) {
            divMore = document.createElement('div')
            divMore.className = 'loadMore'
            showMoreFunction = this.show.bind(this, dom, options, start + options.step, next, divMore)
            dom.appendChild(divMore)

            let a = document.createElement('a')
            a.href = '#'
            a.innerHTML = 'load more'
            a.onclick = () => {
              showMoreFunction()
              return false
            }
            divMore.appendChild(a)
          } else {
            divMore = null
          }

          this.emit('show', {
            result: renderedResult,
            error: null,
            showMoreFunction
          })
        }
      )
    })
  }
}

module.exports = DBApiViewTwig
