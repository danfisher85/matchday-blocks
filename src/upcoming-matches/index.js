import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
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
					<div className="matchday-future-matches">
						<div className="matchday-future-match-card">
							<div className="matchday-future-match-card__header">
								<div className="matchday-future-match-card__tournament">
									Primary League
								</div>
							</div>
							<div className="matchday-future-match-card__content">
								<div className="matchday-future-match-card__teams">
									<div className="matchday-future-match-card__team matchday-future-match-card__team--1">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 1
										</div>
									</div>
									<div className="matchday-future-match-card__vs">VS</div>
									<div className="matchday-future-match-card__team matchday-future-match-card__team--2">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 2
										</div>
									</div>
								</div>
							</div>
							<div className="matchday-future-match-card__footer">
								<div className="matchday-future-match-card__date">
									January 1, 2026
								</div>
								<div className="matchday-future-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-future-match-card">
							<div className="matchday-future-match-card__header">
								<div className="matchday-future-match-card__tournament">
									Primary League
								</div>
							</div>
							<div className="matchday-future-match-card__content">
								<div className="matchday-future-match-card__teams">
									<div className="matchday-future-match-card__team matchday-future-match-card__team--1">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 1
										</div>
									</div>
									<div className="matchday-future-match-card__vs">VS</div>
									<div className="matchday-future-match-card__team matchday-future-match-card__team--2">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 2
										</div>
									</div>
								</div>
							</div>
							<div className="matchday-future-match-card__footer">
								<div className="matchday-future-match-card__date">
									January 1, 2026
								</div>
								<div className="matchday-future-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-future-match-card">
							<div className="matchday-future-match-card__header">
								<div className="matchday-future-match-card__tournament">
									Primary League
								</div>
							</div>
							<div className="matchday-future-match-card__content">
								<div className="matchday-future-match-card__teams">
									<div className="matchday-future-match-card__team matchday-future-match-card__team--1">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 1
										</div>
									</div>
									<div className="matchday-future-match-card__vs">VS</div>
									<div className="matchday-future-match-card__team matchday-future-match-card__team--2">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 2
										</div>
									</div>
								</div>
							</div>
							<div className="matchday-future-match-card__footer">
								<div className="matchday-future-match-card__date">
									January 1, 2026
								</div>
								<div className="matchday-future-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-future-match-card">
							<div className="matchday-future-match-card__header">
								<div className="matchday-future-match-card__tournament">
									Primary League
								</div>
							</div>
							<div className="matchday-future-match-card__content">
								<div className="matchday-future-match-card__teams">
									<div className="matchday-future-match-card__team matchday-future-match-card__team--1">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 1
										</div>
									</div>
									<div className="matchday-future-match-card__vs">VS</div>
									<div className="matchday-future-match-card__team matchday-future-match-card__team--2">
										<div className="matchday-future-match-card__team-logo-placeholder"></div>
										<div className="matchday-future-match-card__team-name">
											Team Name 2
										</div>
									</div>
								</div>
							</div>
							<div className="matchday-future-match-card__footer">
								<div className="matchday-future-match-card__date">
									January 1, 2026
								</div>
								<div className="matchday-future-match-card__time">12:00</div>
							</div>
						</div>
					</div>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
