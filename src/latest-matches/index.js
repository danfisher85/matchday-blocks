import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, Placeholder } from '@wordpress/components';
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
						<div className="matchday-match-card">
							<div className="matchday-match-card__content">
								<div className="matchday-match-card__team matchday-match-card__team--1">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 1</div>
									</div>
									<div className="matchday-match-card__score">22</div>
								</div>
								<div className="matchday-match-card__vs">VS</div>
								<div className="matchday-match-card__team matchday-match-card__team--2">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 2</div>
									</div>
									<div className="matchday-match-card__score">14</div>
								</div>
							</div>
							<div className="matchday-match-card__footer">
								<div className="matchday-match-card__tournament">Primary League</div>
								<div className="matchday-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-match-card">
							<div className="matchday-match-card__content">
								<div className="matchday-match-card__team matchday-match-card__team--1">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 1</div>
									</div>
									<div className="matchday-match-card__score">19</div>
								</div>
								<div className="matchday-match-card__vs">VS</div>
								<div className="matchday-match-card__team matchday-match-card__team--2">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 2</div>
									</div>
									<div className="matchday-match-card__score">12</div>
								</div>
							</div>
							<div className="matchday-match-card__footer">
								<div className="matchday-match-card__tournament">Primary League</div>
								<div className="matchday-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-match-card">
							<div className="matchday-match-card__content">
								<div className="matchday-match-card__team matchday-match-card__team--1">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 1</div>
									</div>
									<div className="matchday-match-card__score">11</div>
								</div>
								<div className="matchday-match-card__vs">VS</div>
								<div className="matchday-match-card__team matchday-match-card__team--2">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 2</div>
									</div>
									<div className="matchday-match-card__score">13</div>
								</div>
							</div>
							<div className="matchday-match-card__footer">
								<div className="matchday-match-card__tournament">Primary League</div>
								<div className="matchday-match-card__time">12:00</div>
							</div>
						</div>
						<div className="matchday-match-card">
							<div className="matchday-match-card__content">
								<div className="matchday-match-card__team matchday-match-card__team--1">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 1</div>
									</div>
									<div className="matchday-match-card__score">15</div>
								</div>
								<div className="matchday-match-card__vs">VS</div>
								<div className="matchday-match-card__team matchday-match-card__team--2">
									<div className="matchday-match-card__team-info">
										<div className="matchday-match-card__logo-placeholder"></div>
										<div className="matchday-match-card__team-name">Team Name 2</div>
									</div>
									<div className="matchday-match-card__score">18</div>
								</div>
							</div>
							<div className="matchday-match-card__footer">
								<div className="matchday-match-card__tournament">Primary League</div>
								<div className="matchday-match-card__time">12:00</div>
							</div>
						</div>
					</div>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
