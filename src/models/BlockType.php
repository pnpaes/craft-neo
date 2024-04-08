<?php

namespace benf\neo\models;

use benf\neo\elements\Block;
use benf\neo\Field;
use benf\neo\fieldlayoutelements\ChildBlocksUiElement;
use benf\neo\Plugin as Neo;
use Craft;
use craft\base\Colorable;
use craft\base\FieldLayoutProviderInterface;
use craft\base\GqlInlineFragmentInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Table;
use craft\elements\Asset;
use craft\enums\Color;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

/**
 * Class BlockType
 *
 * @package benf\neo\models
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @author Benjamin Fleming
 * @since 2.0.0
 */
class BlockType extends Model implements
    FieldLayoutProviderInterface,
    GqlInlineFragmentInterface,
    Colorable
{
    /**
     * @var int|null The block type ID.
     */
    public ?int $id = null;

    /**
     * @var int|null The field ID.
     */
    public ?int $fieldId = null;

    /**
     * @var int|null The field layout ID.
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var int|null The ID of the block type group this block type belongs to, if any.
     * @since 2.13.0
     */
    public ?int $groupId = null;

    /**
     * @var string|null The block type's name.
     */
    public ?string $name = null;

    /**
     * @var string|null The block type's handle.
     */
    public ?string $handle = null;

    /**
     * @var string|null The block type's description.
     * @since 3.0.5
     */
    public ?string $description = null;

    /**
     * @var string|null The block type's icon filename.
     * @since 4.0.0
     */
    public ?string $iconFilename = null;

    /**
     * @var int|null The block type's icon, as a Craft asset ID.
     * @since 3.6.0
     */
    public ?int $iconId = null;

    /**
     * @var Color|null Color
     * @since 5.0.0
     */
    public ?Color $color = null;

    /**
     * @var bool Whether this block type is allowed to be used.
     * @since 3.3.0
     */
    public bool $enabled = true;

    /**
     * @var int|null The minimum number of blocks of this type allowed in this block type's field.
     * @since 3.3.0
     */
    public ?int $minBlocks = null;

    /**
     * @var int|null The maximum number of blocks of this type allowed in this block type's field.
     */
    public ?int $maxBlocks = null;

    /**
     * @var int|null The minimum number of blocks of this type allowed under one parent block.
     * @since 3.3.0
     */
    public ?int $minSiblingBlocks = null;

    /**
     * @var int|null The maximum number of blocks of this type allowed under one parent block.
     * @since 2.8.0
     */
    public ?int $maxSiblingBlocks = null;

    /**
     * @var int|null The minimum number of child blocks.
     * @since 3.3.0
     */
    public ?int $minChildBlocks = null;

    /**
     * @var int|null The maximum number of child blocks.
     */
    public ?int $maxChildBlocks = null;

    /**
     * @var bool Whether the child block types (if any) will be shown in their groups (if any).
     * @since 3.5.0
     */
    public bool $groupChildBlockTypes = true;

    /**
     * @var string[]|string|null The child block types of this block type, either as an array of block type handles, the
     * string '*' representing all of the Neo field's block types, or null if no child block types.
     */
    public array|string|null $childBlocks = null;

    /**
     * @var bool Whether this is at the top level of its field.
     */
    public bool $topLevel = true;

    /**
     * @var bool Whether user permissions for this block type should be ignored.
     * @since 3.5.2
     */
    public bool $ignorePermissions = true;

    /**
     * @var array Conditions for the elements this block type can be used on.
     */
    public array $conditions = [];

    /**
     * @var int|null The sort order.
     */
    public ?int $sortOrder = null;

    /**
     * @var string|null
     */
    public ?string $uid = null;

    /**
     * @var bool
     */
    public bool $hasFieldErrors = false;

    /**
     * @var Field|false|null The Neo field associated with this block type.
     */
    private Field|false|null $_field = null;

    /**
     * @var BlockTypeGroup|null The block type group this block type belongs to, if any.
     */
    private ?BlockTypeGroup $_group = null;

    /**
     * @var bool|null
     */
    private ?bool $_hasChildBlocksUiElement = null;

    /**
     * @var Asset|null
     */
    private ?Asset $_icon = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // `childBlocks` might be a string representing an array
        if (isset($config['childBlocks']) && !is_array($config['childBlocks'])) {
            $config['childBlocks'] = Json::decodeIfJson($config['childBlocks']);
        }

        if (!isset($config['conditions'])) {
            $config['conditions'] = [];
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Block::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['id', 'fieldId', 'sortOrder'], 'number', 'integerOnly' => true],
            [['minBlocks', 'maxBlocks', 'minChildBlocks', 'maxChildBlocks', 'minSiblingBlocks'], 'integer', 'min' => 0],
            [['enabled', 'topLevel', 'groupChildBlockTypes', 'ignorePermissions'], 'boolean'],
        ];
    }

    /**
     * Returns the block type's handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle;
    }

    /**
     * Returns whether this block type is new.
     *
     * @return bool
     */
    public function getIsNew(): bool
    {
        return (!$this->id || strpos($this->id, 'new') === 0);
    }

    /**
     * Returns the Neo field associated with this block type.
     *
     * @return Field|null
     */
    public function getField(): ?Field
    {
        if ($this->_field === null && $this->fieldId) {
            // Ensure the field is still actually a Neo field
            $field = Craft::$app->getFields()->getFieldById($this->fieldId);
            $this->_field = $field instanceof Field ? $field : false;
        }

        return $this->_field ?: null;
    }

    /**
     * Returns the block type group this block type belongs to, if any.
     *
     * @return BlockTypeGroup|null
     * @since 2.13.0
     */
    public function getGroup(): ?BlockTypeGroup
    {
        if ($this->_group === null && $this->groupId !== null) {
            $this->_group = Neo::$plugin->blockTypes->getGroupById($this->groupId);
        }

        return $this->_group;
    }

    /**
     * Gets this block type's icon path, if an icon filename is set.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function getIconPath(?array $transform = null): ?string
    {
        return Neo::$plugin->blockTypes->getIconPath($this, $transform);
    }

    /**
     * Gets this block type's icon URL, if an icon filename is set.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function getIconUrl(?array $transform = null): ?string
    {
        return Neo::$plugin->blockTypes->getIconUrl($this, $transform);
    }

    /**
     * Gets this block type's icon asset, if an icon is set.
     *
     * @return Asset|null
     * @since 3.6.0
     */
    public function getIcon(): ?Asset
    {
        if ($this->_icon === null && $this->iconId !== null) {
            $this->_icon = Craft::$app->getAssets()->getAssetById($this->iconId);
        }

        return $this->_icon;
    }

    /**
     * @inheritdoc
     */
    public function getColor(): ?Color
    {
        return $this->color;
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext(): string
    {
        return 'global';
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingPrefix(): string
    {
        return $this->handle;
    }

    /**
     * Returns the block type config.
     *
     * @return array
     * @since 2.9.0
     */
    public function getConfig(): array
    {
        $group = $this->getGroup();
        $icon = $this->getIcon();

        if ($icon) {
            $iconData = [
                'volume' => $icon->getVolume()->uid,
                'folderPath' => $icon->getFolder()->path,
                'filename' => $icon->getFilename(),
            ];
        } else {
            $iconData = null;
        }

        $config = [
            'childBlocks' => $this->childBlocks,
            'field' => $this->getField()?->uid,
            'group' => $group ? $group->uid : null,
            'groupChildBlockTypes' => (bool)$this->groupChildBlockTypes,
            'handle' => $this->handle,
            'description' => $this->description ?? '',
            'enabled' => $this->enabled,
            'iconFilename' => $this->iconFilename ?? '',
            'icon' => $iconData,
            'color' => $this->color?->value,
            'minBlocks' => (int)$this->minBlocks,
            'maxBlocks' => (int)$this->maxBlocks,
            'minChildBlocks' => (int)$this->minChildBlocks,
            'maxChildBlocks' => (int)$this->maxChildBlocks,
            'minSiblingBlocks' => (int)$this->minSiblingBlocks,
            'maxSiblingBlocks' => (int)$this->maxSiblingBlocks,
            'name' => $this->name,
            'sortOrder' => (int)$this->sortOrder,
            'topLevel' => (bool)$this->topLevel,
            'ignorePermissions' => (bool)$this->ignorePermissions,
            'conditions' => $this->conditions ?: null,
        ];
        $fieldLayout = $this->getFieldLayout();

        // Field layout ID might not be set even if the block type already had one -- just grab it from the block type
        $fieldLayout->id = $fieldLayout->id ?? $this->fieldLayoutId;
        $fieldLayoutConfig = $fieldLayout->getConfig();

        // No need to bother with the field layout if it has no tabs
        if ($fieldLayoutConfig !== null) {
            $fieldLayoutUid = $fieldLayout->uid ??
                ($fieldLayout->id ? Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id) : null) ??
                StringHelper::UUID();

            if (!$fieldLayout->uid) {
                $fieldLayout->uid = $fieldLayoutUid;
            }

            $config['fieldLayouts'][$fieldLayoutUid] = $fieldLayoutConfig;
        }

        return $config;
    }

    /**
     * Returns whether this block type's field layout contains the child blocks UI element.
     *
     * @return bool
     * @since 3.0.0
     */
    public function hasChildBlocksUiElement(): bool
    {
        if ($this->_hasChildBlocksUiElement !== null) {
            return $this->_hasChildBlocksUiElement;
        }

        foreach ($this->getFieldLayout()->getTabs() as $tab) {
            foreach ($tab->elements as $element) {
                if ($element instanceof ChildBlocksUiElement) {
                    return $this->_hasChildBlocksUiElement = true;
                }
            }
        }

        return $this->_hasChildBlocksUiElement = false;
    }

    // FieldLayoutProviderInterface methods

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): FieldLayout
    {
        return $this->getBehavior('fieldLayout')->getFieldLayout();
    }
}
