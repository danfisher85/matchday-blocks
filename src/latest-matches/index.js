import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('matchday/latest-matches', {
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
					<div className="matchday-latest-matches">
						{Array.from({ length: attributes.limit || 5 }).map((_, index) => (
							<div key={index} className="matchday-match-card">
								<div className="matchday-match-card__content">
									<div className="matchday-match-card__team matchday-match-card__team--1">
										<div className="matchday-match-card__team-info">
											<div className="matchday-match-card__logo-placeholder"></div>
											<div className="matchday-match-card__team-name">Team Name 1</div>
										</div>
										<div className="matchday-match-card__score">0</div>
									</div>
									<div className="matchday-match-card__vs">VS</div>
									<div className="matchday-match-card__team matchday-match-card__team--2">
										<div className="matchday-match-card__team-info">
											<div className="matchday-match-card__logo-placeholder"></div>
											<div className="matchday-match-card__team-name">Team Name 2</div>
										</div>
										<div className="matchday-match-card__score">0</div>
									</div>
								</div>
								<div className="matchday-match-card__footer">
									<div className="matchday-match-card__tournament">Primary League</div>
									<div className="matchday-match-card__time">12:00</div>
								</div>
							</div>
						))}
					</div>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
