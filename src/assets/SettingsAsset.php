<?php

namespace benf\neo\assets;

use benf\neo\elements\Block;
use benf\neo\Field;
use benf\neo\models\BlockType;
use benf\neo\models\BlockTypeGroup;
use benf\neo\Plugin as Neo;
use Craft;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class SettingsAsset
 *
 * @package benf\neo\assets
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @author Benjamin Fleming
 * @since 3.0.0
 */
class SettingsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@benf/neo/assets/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = ['neo-configurator.css'];
        $this->js = [
            'neo-configurator.js',
            'neo-converter.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        $view->registerTranslations('neo', [
            'Actions',
            'Copy',
            'Paste',
            'Clone',
            'Delete',
            'Reorder',
            'Name',
            'What this block type will be called in the CP.',
            'Handle',
            'How you’ll refer to this block type in the templates.',
            'Description',
            'Enabled',
            'Whether this block type is allowed to be used.',
            'Ignore Permissions',
            'Whether user permissions for this block type should be ignored.',
            'Max Blocks',
            'The maximum number of blocks of this type the field is allowed to have.',
            'All',
            'Child Blocks',
            'Which block types do you want to allow as children?',
            'Max Child Blocks',
            'The maximum number of child blocks this block type is allowed to have.',
            'Top Level',
            'Will this block type be allowed at the top level?',
            'Delete block type',
            'This can be left blank if you just want an unlabeled separator.',
            'Show',
            'Hide',
            'Use global setting (Show)',
            'Use global setting (Hide)',
            'Always Show Dropdown?',
            'Whether to show the dropdown for this group if it only has one available block type.',
            'Delete group',
            'Couldn’t copy block type.',
            'Couldn’t create new block type.',
            'Add',
            'Replace',
            'None set',
        ]);

        parent::registerAssetFiles($view);
    }

    /**
     * Sets up the field layout designer for a given Neo field.
     *
     * @param Field $field The Neo field.
     * @return string
     */
    public static function createSettingsJs(Field $field): string
    {
        $blockTypes = $field->getBlockTypes();
        $blockTypeGroups = $field->getGroups();
        $settings = Neo::$plugin->blockTypes->renderSettings();
        $fieldLayoutHtml = Neo::$plugin->blockTypes->renderFieldLayoutDesigner(new FieldLayout(['type' => Block::class]));

        $jsSettings = [
            'namespace' => Craft::$app->getView()->getNamespace(),
            'blockTypes' => self::_getBlockTypesJsSettings($blockTypes),
            'groups' => self::_getBlockTypeGroupsJsSettings($blockTypeGroups),
            'blockTypeSettingsHtml' => $settings['settingsHtml'],
            'blockTypeSettingsJs' => $settings['settingsJs'],
            'fieldLayoutHtml' => $fieldLayoutHtml,
            'blockTypeGroupSettingsHtml' => Neo::$plugin->blockTypes->renderBlockTypeGroupSettings()[0],
            'defaultAlwaysShowGroupDropdowns' => Neo::$plugin->settings->defaultAlwaysShowGroupDropdowns,
        ];

        $encodedJsSettings = Json::encode($jsSettings, JSON_UNESCAPED_UNICODE);

        return "Neo.createConfigurator($encodedJsSettings)";
    }

    /**
     * Returns the raw data from the given block types, in the format used by the settings generator JavaScript.
     *
     * @param BlockType[] $blockTypes
     * @return array
     */
    private static function _getBlockTypesJsSettings(array $blockTypes): array
    {
        $view = Craft::$app->getView();
        $jsBlockTypes = [];
        $newX = 0;

        foreach ($blockTypes as $blockType) {
            // Ensure the field layout UID is set with the config, to prevent the UID from being regenerated on save
            $fieldLayout = $blockType->getFieldLayout();
            $fieldLayoutConfig = $fieldLayout->getConfig();
            $fieldLayoutConfig['uid'] = $fieldLayout->uid;
            $jsBlockTypes[] = [
                'id' => $blockType->id ?: 'new' . $newX++,
                'sortOrder' => $blockType->sortOrder,
                'name' => $blockType->name,
                'handle' => $blockType->handle,
                'enabled' => $blockType->enabled,
                'ignorePermissions' => $blockType->ignorePermissions,
                'description' => $blockType->description,
                'iconFilename' => $blockType->iconFilename,
                'iconId' => $blockType->iconId,
                'minBlocks' => $blockType->minBlocks,
                'maxBlocks' => $blockType->maxBlocks,
                'minSiblingBlocks' => $blockType->minSiblingBlocks,
                'maxSiblingBlocks' => $blockType->maxSiblingBlocks,
                'minChildBlocks' => $blockType->minChildBlocks,
                'maxChildBlocks' => $blockType->maxChildBlocks,
                'groupChildBlockTypes' => (bool)$blockType->groupChildBlockTypes,
                'childBlocks' => is_string($blockType->childBlocks) ? Json::decodeIfJson($blockType->childBlocks) : $blockType->childBlocks,
                'topLevel' => (bool)$blockType->topLevel,
                'errors' => $blockType->getErrors(),
                'fieldLayoutId' => $blockType->fieldLayoutId,
                'fieldLayoutConfig' => $fieldLayoutConfig,
                'groupId' => $blockType->groupId,
            ];
        }

        return $jsBlockTypes;
    }

    /**
     * Returns the raw data from the given block type groups, in the format used by the settings generator JavaScript.
     *
     * @param BlockTypeGroup[] $blockTypeGroups The Neo block type groups.
     * @return array
     */
    private static function _getBlockTypeGroupsJsSettings(array $blockTypeGroups): array
    {
        $jsBlockTypeGroups = [];
        $newX = 0;

        foreach ($blockTypeGroups as $blockTypeGroup) {
            $jsBlockTypeGroups[] = [
                'id' => $blockTypeGroup->id ?: 'new' . $newX++,
                'sortOrder' => $blockTypeGroup->sortOrder,
                'name' => Craft::t('site', $blockTypeGroup->name),
                'alwaysShowDropdown' => $blockTypeGroup->alwaysShowDropdown,
            ];
        }

        return $jsBlockTypeGroups;
    }
}
