const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')
const async = {
  each: require('async/eachOf'),
  parallel: require('async/parallel')
}

let _CacheCallback
let _NeedReload

class DBApiViewTwig extends DBApiView {
  constructor (dbApi, def, options) {
    super(dbApi, def, options)

    this.twig = this.options.twig
    this.twig.extendFilter('dbApiGet', function (value, args) {
      if (value === null) {
        return null
      }

      let result = dbApi.getCached(args[0], value, _CacheCallback)
      _CacheCallback = null

      if (typeof result === 'undefined') {
        _NeedReload = true
      }
      return result
    })
  }

  init (callback) {
    this.template = this.twig.twig({
      data: Array.isArray(this.def.each) ? this.def.each.join('\n') : this.def.each,
      autoescape: true
    })
    if (this.def.pre) {
      this.templatePre = this.twig.twig({
        data: Array.isArray(this.def.pre) ? this.def.pre.join('\n') : this.def.pre,
        autoescape: true
      })
    }
    if (this.def.post) {
      this.templatePost = this.twig.twig({
        data: Array.isArray(this.def.post) ? this.def.post.join('\n') : this.def.post,
        autoescape: true
      })
    }

    callback()
  }

  render (data, template, callback) {
    let result

    _NeedReload = false
    _CacheCallback = () => {
      callback(null, template.render(data))
    }

    result = template.render(data)

    if (!_NeedReload) {
      callback(null, result)
    }
  }

  show (dom, options={}, callback=null, start=0, next=null, divMore=null) {
    if (typeof this.template === 'undefined') {
      return this.init((err) => {
        if (err) {
          if (callback) {
            callback(err)
            callback = null
          }
          return this.emit('show', {
            error: err
          })
        }

        this.show(dom, options, callback, start, next, divMore)
      })
    }

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
        if (callback) {
          callback(err)
          callback = null
        }
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

      async.parallel([
        (done) => {
          if (start === 0 && this.templatePre) {
            let div = document.createElement('div')
            div.className = 'pre'
            dom.appendChild(div)

            let data = {
              global: options.global
            }

            this.render(data, this.templatePre, (err, result) => {
              div.innerHTML = result
              done(err)
            })
          } else {
            done()
          }
        },

        (done) => {
          if (start === 0 && this.templatePost) {
            let div = document.createElement('div')
            div.className = 'post'
            dom.appendChild(div)

            let data = {
              global: options.global
            }

            this.render(data, this.templatePost, (err, result) => {
              div.innerHTML = result
              done(err)
            })
          } else {
            done()
          }
        },

        (done) => {
          async.each(result,
            (entry, index, callback) => {
              let div = document.createElement('div')
              div.className = 'entry'
              if (dom.lastChild && dom.lastChild.className === 'post') {
                dom.insertBefore(div, dom.lastChild)
              } else {
                dom.appendChild(div)
              }

              let data = {
                entry: entry,
                global: options.global
              }

              this.render(data, this.template, (err, r) => {
                div.innerHTML = r
                renderedResult[index] = r

                this.emit('showEntry', {
                  dom: div,
                  entry,
                  table: this.query.table,
                  error: null
                })

                callback(err)
              })
            },
            (err) => {
              done()
            }
          )}
        ],
        () => {
          let showMoreFunction
          if (divMore) {
            dom.removeChild(divMore)
          }
          if (next) {
            divMore = document.createElement('div')
            divMore.className = 'loadMore'
            showMoreFunction = this.show.bind(this, dom, options, callback, start + options.step, next, divMore)
            if (dom.lastChild && dom.lastChild.className === 'post') {
              dom.insertBefore(divMore, dom.lastChild)
            } else {
              dom.appendChild(divMore)
            }

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

          if (callback) {
            callback(null)
            callback = null
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
