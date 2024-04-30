import Garnish from 'garnish'

const _defaults = {
  id: -1,
  sortOrder: 0,
  alwaysShowDropdown: null,
  name: ''
}

export default Garnish.Base.extend({

  init (settings = {}) {
    settings = Object.assign({}, _defaults, settings)

    this._id = settings.id | 0
    this._sortOrder = settings.sortOrder | 0
    this._alwaysShowDropdown = settings.alwaysShowDropdown
    this._name = settings.name
  },

  getType () { return 'group' },
  getId () { return this._id },
  getSortOrder () { return this._sortOrder },
  getName () { return this._name },
  getAlwaysShowDropdown () { return this._alwaysShowDropdown },
  isBlank () { return !this._name }
})
