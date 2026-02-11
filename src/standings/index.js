import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('matchday/standings', {
	edit: ({ attributes, setAttributes }) => {
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Settings', 'matchday-blocks')}>
						<TextControl
							label={__('Group', 'matchday-blocks')}
							value={attributes.group}
							onChange={(value) => setAttributes({ group: value })}
							help={__('Leave empty to show all groups, or enter a group ID (e.g., "A", "B")', 'matchday-blocks')}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<Placeholder
						icon="list-view"
						label={__('Tournament Standings', 'matchday-blocks')}
					>
						{__('This block will display tournament standings from MeinTurnierplan.', 'matchday-blocks')}
					</Placeholder>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
