import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
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
					<div className="matchday-match-schedule">
						<div className="matchday-match-schedule__stage">
							<h3 className="matchday-match-schedule__stage-heading">First Round</h3>
							<div className="matchday-match-schedule__date">
								<h4 className="matchday-match-schedule__date-heading">Thursday, January 1, 2026</h4>
								<div className="matchday-match-schedule__table-wrapper">
									<table>
										<thead>
											<tr>
												<th>№</th>
												<th>Start</th>
												<th>Gr</th>
												<th colSpan="3">Match</th>
												<th>Result</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>1</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">A</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 1</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 2</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>2</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">B</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 3</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 4</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>3</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">A</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 5</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 6</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>4</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">B</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 7</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 8</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>5</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">A</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 9</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 10</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>6</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">B</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 11</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 12</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>7</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">A</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 13</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 14</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>8</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">B</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 15</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 16</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>9</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">A</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 17</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 18</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
											<tr>
												<td>10</td>
												<td>12:00</td>
												<td>
													<span className="matchday-group-badge">B</span>
												</td>
												<td className="matchday-table__participant matchday-table__participant--team1">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 19</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-vs">:</td>
												<td className="matchday-table__participant matchday-table__participant--team2">
													<div className="matchday-table__participant-inner">
														<div className="matchday-table__participant-logo-placeholder" />
														<div className="matchday-table__participant-info">
															<div className="matchday-table__participant-name">Team Name 20</div>
														</div>
													</div>
												</td>
												<td className="matchday-match-final-score">0:0</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
