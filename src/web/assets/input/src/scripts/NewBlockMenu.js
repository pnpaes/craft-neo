import $ from 'jquery'
import Craft from 'craft'
import Garnish from 'garnish'

const _defaults = {
  $ownerContainer: null,
  blockTypes: [],
  groups: [],
  items: null,
  maxBlocks: 0,
  maxTopBlocks: 0,
  blocks: null
}

class NewBlockMenu {
  static BUTTON_INFO = 'data-neo-bn-info'
  _blockTypes = []
  _blockTypeGroups = []

  constructor (settings = {}) {
    settings = Object.assign({}, _defaults, settings)

    if (settings.items) {
      this._items = Array.from(settings.items)
      this._blockTypes = this._items.filter(i => i.getType() === 'blockType')
      this._blockTypeGroups = this._items.filter(i => i.getType() === 'group')
    } else {
      this._blockTypes = Array.from(settings.blockTypes)
      this._blockTypeGroups = Array.from(settings.groups)
      this._items = [...this._blockTypes, ...this._blockTypeGroups].sort((a, b) => a.getSortOrder() - b.getSortOrder())
    }

    this.$ownerContainer = settings.$ownerContainer
    this._field = settings.field
    this._maxBlocks = settings.maxBlocks | 0
    this._maxTopBlocks = settings.maxTopBlocks | 0

    this.$container = this.renderButtons()

    const $neo = this.$container.find('[data-neo-bn]')
    this.$buttonsContainer = $neo.filter('[data-neo-bn="container.buttons"]')
    this.$menuContainer = $neo.filter('[data-neo-bn="container.menu"]')
    this.$blockButtons = $neo.filter('[data-neo-bn="button.addBlock"]')
    this.$groupButtons = $neo.filter('[data-neo-bn="button.group"]')

    if (settings.blocks) {
      this.updateState(settings.blocks)
    }
  }

  /**
   * @since 3.6.0
   * @protected
   * @returns string
   */
  renderButtons () {
    const ownerBlockType = this.$ownerContainer?.hasClass('ni_block')
      ? this.$ownerContainer.attr('class').match(/ni_block--([^\s]+)/)[1]
      : null
    const ungroupChildBlockTypes = ownerBlockType !== null &&
      !this.getField().getBlockTypeByHandle(ownerBlockType).getGroupChildBlockTypes()
    const buttonsHtml = []
    let blockTypesHtml = []
    let currentGroup = null
    let firstButton = true

    const generateGroupDropdown = () => {
      buttonsHtml.push(`
          <div class="btn dashed${firstButton ? ' add icon' : ''} menubtn" data-neo-bn="button.group">
            ${currentGroup.getName()}
          </div>
          <div class="menu">
            <ul>${blockTypesHtml.join('')}
            </ul>
          </div>`)
      firstButton = false
      blockTypesHtml = []
    }

    buttonsHtml.push(`
      <div class="ni_buttons">
        <div class="btngroup" data-neo-bn="container.buttons">`)

    for (let i = 0; i < this._items.length; i++) {
      const item = this._items[i]
      const type = item.getType()

      if (type === 'blockType') {
        // Ignore disabled block types, or block types for which the current user isn't allowed to create blocks
        if (!item.getEnabled() || !item.isCreatableByUser()) {
          continue
        }

        const titleAttr = item.getDescription() ? ` title="${item.getDescription()}"` : ''

        if (currentGroup !== null) {
          blockTypesHtml.push(`
            <li>
              <a${titleAttr} aria-label="${item.getName()}" data-neo-bn="button.addBlock" ${NewBlockMenu.BUTTON_INFO}="${item.getHandle()}">${item.getName()}</a>
            </li>`)
        } else {
          buttonsHtml.push(`
          <button${titleAttr} aria-label="${item.getName()}" class="btn dashed${firstButton ? ' add icon' : ''}" data-neo-bn="button.addBlock" ${NewBlockMenu.BUTTON_INFO}="${item.getHandle()}">
            ${item.getName()}
          </button>`)
          firstButton = false
        }
      } else if (type === 'group') {
        if (currentGroup !== null && blockTypesHtml.length > 0) {
          generateGroupDropdown()
        }

        if (
          // Don't show dropdowns for groups with blank names, as they're just used to end the previous group
          (item.isBlank()) ||
          // Don't show dropdowns if we're not forcing them to show, and there's only one block type in this group
          (!item.getAlwaysShowDropdown() && ((i + 2) >= this._items.length || this._items[i + 2].getType() === 'group')) ||
          // Don't show dropdowns if the block type is set not to group child block types
          (ungroupChildBlockTypes)
        ) {
          currentGroup = null
        } else {
          currentGroup = item
        }
      }
    }

    if (currentGroup !== null && blockTypesHtml.length > 0) {
      generateGroupDropdown()
    }

    buttonsHtml.push(`
        </div>
        <div class="btn dashed add icon menubtn hidden" data-neo-bn="container.menu">
          ${Craft.t('neo', 'Add a block')}
        </div>`)

    // Menu, for views where the buttons would exceed the editor width
    currentGroup = null
    let lastGroupHadBlockTypes = false
    buttonsHtml.push(`
        <div class="menu">
          <ul>`)

    for (const item of this._items) {
      const type = item.getType()

      if (type === 'blockType') {
        // Ignore disabled block types, or block types for which the current user isn't allowed to create blocks
        if (!item.getEnabled() || !item.isCreatableByUser()) {
          continue
        }

        if (currentGroup !== null && !lastGroupHadBlockTypes) {
          lastGroupHadBlockTypes = true

          buttonsHtml.push(`
              <h6>${currentGroup.getName()}</h6>
              <ul class="padded">`)
        }

        const titleAttr = item.getDescription() ? ` title="${item.getDescription()}"` : ''
        buttonsHtml.push(`
            <li>
              <a${titleAttr} aria-label="${item.getName()}" data-neo-bn="button.addBlock" ${NewBlockMenu.BUTTON_INFO}="${item.getHandle()}">
                ${item.getName()}
              </a>
            </li>`)
      } else if (type === 'group') {
        if (currentGroup === null || lastGroupHadBlockTypes) {
          buttonsHtml.push(`
              </ul>`)
        }

        lastGroupHadBlockTypes = false
        currentGroup = item.isBlank() || ungroupChildBlockTypes ? null : item

        if (currentGroup === null) {
          buttonsHtml.push(`
              <ul>`)
        }
      }
    }

    buttonsHtml.push(`
          </ul>
        </div>
      </div>`)

    return $(buttonsHtml.join(''))
  }

  getField () {
    return this._field
  }

  getBlockTypes () {
    return Array.from(this._blockTypes)
  }

  getBlockTypeGroups () {
    return Array.from(this._blockTypeGroups)
  }

  getBlockTypeByButton ($button) {
    const btHandle = $button.attr(NewBlockMenu.BUTTON_INFO)

    return this._blockTypes.find(bt => bt.getHandle() === btHandle)
  }

  updateState (blocks = [], additionalCheck = null, block = null) {
    additionalCheck = typeof additionalCheck === 'boolean' ? additionalCheck : true

    const that = this

    const totalTopBlocks = blocks.filter(block => block.isTopLevel()).length
    const maxBlocksMet = this._maxBlocks > 0 && blocks.length >= this._maxBlocks
    const maxTopBlocksMet = this._maxTopBlocks > 0 && totalTopBlocks >= this._maxTopBlocks

    const allDisabled = maxBlocksMet || maxTopBlocksMet || !additionalCheck

    this.$blockButtons.each(function () {
      const $button = $(this)
      let disabled = allDisabled

      if (!disabled) {
        const blockHasSameType = b => b.getBlockType().getHandle() === blockType.getHandle()
        const blockType = that.getBlockTypeByButton($button)
        const blocksOfType = blocks.filter(blockHasSameType)
        const maxBlocksOfType = blockType.getMaxBlocks()

        const maxSiblingBlocks = blockType.getMaxSiblingBlocks()
        const siblingBlocksOfType = block !== null
          ? block.getChildren(blocks).filter(blockHasSameType)
          // This is at the top level
          : blocks.filter(b => b.isTopLevel() && b.getBlockType().getHandle() === blockType.getHandle())

        disabled ||= (maxBlocksOfType > 0 && blocksOfType.length >= maxBlocksOfType) ||
          (maxSiblingBlocks > 0 && siblingBlocksOfType.length >= maxSiblingBlocks)
      }

      $button.toggleClass('disabled', disabled)
    })

    this.$groupButtons.each(function () {
      const $button = $(this)
      const menu = $button.data('menubtn')
      let disabled = allDisabled

      if (!disabled && menu) {
        const $menuButtons = menu.menu.$options
        disabled = $menuButtons.length === $menuButtons.filter('.disabled').length
      }

      $button.toggleClass('disabled', disabled)
    })
  }

  updateResponsiveness () {}
}

const GarnishNewBlockMenu = Garnish.Base.extend({

  init (buttons) {
    this._buttons = buttons
    this.$container = this._buttons.$container
    this.addListener(this._buttons.$blockButtons, 'activate', '@newBlock')
  },

  initUi () {
    this._buttons.initUi()
  },

  getBlockTypes () {
    return this._buttons.getBlockTypes()
  },

  getGroups () {
    return this._buttons.getBlockTypeGroups()
  },

  getMaxBlocks () {
    return this._maxBlocks
  },

  updateButtonStates (blocks = [], additionalCheck = null, block = null) {
    this._buttons.updateState(blocks, additionalCheck, block)
  },

  updateState (blocks = [], additionalCheck = null, block = null) {
    this._buttons.updateState(blocks, additionalCheck, block)
  },

  updateResponsiveness () {
    this._buttons.updateResponsiveness()
  },

  getBlockTypeByButton ($button) {
    return this._buttons.getBlockTypeByButton($button)
  },

  '@newBlock' (e) {
    const $button = $(e.currentTarget)
    const blockTypeHandle = $button.attr(NewBlockMenu.BUTTON_INFO)
    const blockType = this._buttons.getBlockTypes().find(bt => bt.getHandle() === blockTypeHandle)

    this.trigger('newBlock', {
      blockType
    })
  }
})

export { NewBlockMenu, GarnishNewBlockMenu }
