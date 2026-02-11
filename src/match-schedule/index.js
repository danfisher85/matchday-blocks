import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('matchday/match-schedule', {
	edit: ({ attributes, setAttributes }) => {
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Settings', 'matchday-blocks')}>
						<TextControl
							label={__('Date Filter', 'matchday-blocks')}
							value={attributes.date}
							onChange={(value) => setAttributes({ date: value })}
							help={__('Leave empty to show all dates, or enter a specific date (YYYY-MM-DD)', 'matchday-blocks')}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<Placeholder
						icon="calendar-alt"
						label={__('Match Schedule', 'matchday-blocks')}
					>
						{__('This block will display the complete match schedule from MeinTurnierplan.', 'matchday-blocks')}
					</Placeholder>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
