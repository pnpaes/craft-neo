import $ from 'jquery'
import Craft from 'craft'
import Garnish from 'garnish'
import Item from './Item'
import NS from './namespace'
import BlockTypeFieldLayout from './BlockTypeFieldLayout'

const _defaults = {
  namespace: [],
  fieldLayout: null,
  alreadyLoaded: false
}

export default Item.extend({

  _templateNs: [],
  _loaded: false,
  _initialisedUi: false,

  init (settings = {}) {
    this.base(settings)

    const settingsObj = this.getSettings()
    settings = Object.assign({}, _defaults, settings)

    this._templateNs = NS.parse(settings.namespace)
    this._field = settings.field
    this._fieldLayout = settings.fieldLayout
    this._loaded = settings.alreadyLoaded
    const sidebarItem = this.getField()?.$sidebarContainer.find(`[data-neo-bt="container.${this.getId()}`)

    if (sidebarItem?.length > 0) {
      this.$container = sidebarItem
    } else {
      this.$container = this._generateBlockType(settingsObj)
    }

    const $neo = this.$container.find('[data-neo-bt]')
    this.$nameText = $neo.filter('[data-neo-bt="text.name"]')
    this.$handleText = $neo.filter('[data-neo-bt="text.handle"]')
    this.$moveButton = $neo.filter('[data-neo-bt="button.move"]')
    this.$actionsButton = $neo.filter('[data-neo-bt="button.actions"]')

    // Manually select the block type, instead of letting the actions button click close the menu
    this.addListener(this.$actionsButton, 'click', (e) => {
      e.stopPropagation()
      this.getField().selectItem(this)
    })

    this.deselect()
  },

  _generateBlockType (settings) {
    const sortOrderNamespace = [...this._templateNs]
    sortOrderNamespace.pop()
    NS.enter(sortOrderNamespace)
    const sortOrderName = NS.fieldName('sortOrder')
    NS.leave()
    const errors = settings.getErrors()
    const hasErrors = (Array.isArray(errors) ? errors : Object.keys(errors)).length > 0
    const menuId = `actionmenu${Math.floor(Math.random() * 1000000)}`

    return $(`
      <div class="nc_sidebar_list_item${hasErrors ? ' has-errors' : ''}" data-neo-bt="container.${this.getId()}">
        <div class="label" data-neo-bt="text.name">${settings.getName()}</div>
        <div class="smalltext light code" data-neo-bt="text.handle">${settings.getHandle()}</div>
        <a class="move icon" title="${Craft.t('neo', 'Reorder')}" role="button" data-neo-bt="button.move"></a>
        <button class="btn action-btn menubtn" title="${Craft.t('neo', 'Actions')}" role="button" type="button" aria-controls="${menuId}" data-neo-bt="button.actions"></button>
        <div id="${menuId}" class="menu menu--disclosure" data-neo-bt="container.menu">
          <ul>
            <li><button class="menu-item" data-icon="field" data-action="copy">${Craft.t('neo', 'Copy')}</button></li>
            <li class="disabled"><button class="menu-item" data-icon="brush" data-action="paste">${Craft.t('neo', 'Paste')}</button></li>
            <li><button class="menu-item" data-icon="share" data-action="clone">${Craft.t('neo', 'Clone')}</button></li>
            <li><button class="menu-item error" data-icon="remove" data-action="delete">${Craft.t('neo', 'Delete')}</button></li>
          </ul>
        </div>
        <input type="hidden" name="${sortOrderName}[]" value="blocktype:${this.getId()}" data-neo-gs="input.sortOrder">
      </div>`)
  },

  getId () {
    return this.getSettings().getId()
  },

  getFieldLayout () {
    return this._fieldLayout
  },

  /**
   * @inheritDoc
   */
  load () {
    if (`${this.getId()}`.startsWith('new')) {
      // New block types should already be loaded
      this._loaded = true
    }

    if (this._loaded) {
      // Already loaded (though make sure the UI is initialised as well)
      this._initUi()
      return Promise.resolve()
    }

    this.trigger('beforeLoad')
    const settings = this.getSettings()
    // Don't overwrite the field layout if it's already set (e.g. if pasting a block type)
    const fieldLayout = this.getFieldLayout()?.getConfig() ?? settings.getFieldLayoutConfig()
    const fieldLayoutId = settings.getFieldLayoutId()
    const data = {
      blockTypeId: this.getId(),
      errors: settings.getErrors(),
      fieldLayout
    }

    return new Promise((resolve, reject) => {
      Craft.sendActionRequest('POST', 'neo/configurator/render-block-type', { data })
        .then(response => {
          if (response.data.headHtml) {
            Craft.appendHeadHtml(response.data.headHtml)
          }

          if (response.data.bodyHtml) {
            Craft.appendBodyHtml(response.data.bodyHtml)
          }

          this._fieldLayout = new BlockTypeFieldLayout({
            namespace: [...this._templateNs, this._id],
            html: response.data.fieldLayoutHtml,
            id: fieldLayoutId,
            blockTypeId: data.blockTypeId,
            initUi: false
          })
          this._settings.createContainer({
            html: response.data.settingsHtml.replace(/__NEOBLOCKTYPE_ID__/g, data.blockTypeId),
            js: response.data.settingsJs.replace(/__NEOBLOCKTYPE_ID__/g, data.blockTypeId)
          })
          this._loaded = true

          if (this.getId()) {
            this.getField().addItem(this)
            this._initUi()
          }

          this.trigger('afterLoad')
          resolve()
        })
        .catch(reject)
    })
  },

  _initUi () {
    if (!this._loaded || this._initialisedUi) {
      return
    }

    const settingsObj = this.getSettings()

    // Set up the actions menu
    this._actionsMenu = this.$actionsButton.data('trigger') || new Garnish.DisclosureMenu(this.$actionsButton)
    this.$actionsMenu = this._actionsMenu.$container
    this.addListener(this.$actionsMenu.find('[data-action]'), 'click', this['@actionSelect'])

    if (settingsObj) {
      settingsObj.on('change', () => this._updateTemplate())
      settingsObj.on('destroy', () => this.trigger('destroy'))

      this._updateTemplate()
    }

    // Make sure menu states (for pasting block types) are updated when changing browser tabs
    const refreshPasteOptions = () => this.$actionsMenu
      .find('[data-action="paste"]')
      .parent()
      .toggleClass('disabled', !window.localStorage.getItem('neo:copyBlockType'))
    refreshPasteOptions()
    this.addListener(document, `visibilitychange.blocktype${this.getId()}`, refreshPasteOptions)

    this.getSettings()?.initUi()
    this.getFieldLayout()?.initUi()
    this._initialisedUi = true
  },

  /**
   * @since 5.0.0
   */
  getConfig () {
    const settings = this.getSettings()

    return {
      settings: {
        childBlocks: settings.getChildBlocks(),
        conditions: settings.getConditions(),
        description: settings.getDescription(),
        enabled: settings.getEnabled(),
        iconId: settings.getIconId(),
        ignorePermissions: settings.getIgnorePermissions(),
        handle: settings.getHandle(),
        minBlocks: settings.getMinBlocks(),
        maxBlocks: settings.getMaxBlocks(),
        minChildBlocks: settings.getMinChildBlocks(),
        maxChildBlocks: settings.getMaxChildBlocks(),
        minSiblingBlocks: settings.getMinSiblingBlocks(),
        maxSiblingBlocks: settings.getMaxSiblingBlocks(),
        name: settings.getName(),
        topLevel: settings.getTopLevel()
      },
      fieldLayout: this.getFieldLayout().getConfig()
    }
  },

  toggleSelect: function (select) {
    this.base(select)

    const settings = this.getSettings()
    const fieldLayout = this.getFieldLayout()
    const selected = this.isSelected()

    if (settings?.$container ?? false) {
      settings.$container.toggleClass('hidden', !selected)
    }

    if (fieldLayout) {
      fieldLayout.$container.toggleClass('hidden', !selected)
    }

    if (selected) {
      this.load()
    }

    this.$container.toggleClass('is-selected', selected)
  },

  _updateTemplate () {
    const settings = this.getSettings()

    if (settings) {
      this.$nameText.text(settings.getName())
      this.$handleText.text(settings.getHandle())
      this.$container.toggleClass('is-child', !settings.getTopLevel())
    }
  },

  '@actionSelect' (e) {
    const option = e.target

    if (option.classList.contains('disabled')) {
      return
    }

    this._actionsMenu?.hide()

    switch (option.getAttribute('data-action')) {
      case 'copy':
        this.trigger('copy')
        break
      case 'paste':
        this.trigger('paste')
        break
      case 'clone':
        this.trigger('clone')
        break
      case 'delete':
        if (window.confirm(Craft.t('neo', 'Are you sure you want to delete this block type?'))) {
          this.getSettings().destroy()
        }
    }
  }
})
