import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('matchday/upcoming-matches', {
	edit: ({ attributes, setAttributes }) => {
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Settings', 'matchday-blocks')}>
						<RangeControl
							label={__('Number of Matches', 'matchday-blocks')}
							value={attributes.limit}
							onChange={(value) => setAttributes({ limit: value })}
							min={1}
							max={20}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<Placeholder
						icon="calendar"
						label={__('Upcoming Matches', 'matchday-blocks')}
					>
						{__('This block will display upcoming scheduled matches from MeinTurnierplan.', 'matchday-blocks')}
					</Placeholder>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
