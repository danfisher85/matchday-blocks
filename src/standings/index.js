import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
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
					<div className="matchday-standings">
						<div className="matchday-standings__group">
							<h3 className="matchday-standings__group-heading">Group A</h3>
							<div className="matchday-standings__table-wrapper">
								<table>
									<thead>
										<tr>
											<th className="matchday-table__pos">Pl</th>
											<th className="matchday-table__participant">Participants</th>
											<th>M</th>
											<th>G</th>
											<th>GD</th>
											<th className="matchday-table__pts">Pts</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td className="matchday-table__pos">1st</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 1
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">2nd</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 2
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">3rd</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 3
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">4th</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 4
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div className="matchday-standings__group">
							<h3 className="matchday-standings__group-heading">Group B</h3>
							<div className="matchday-standings__table-wrapper">
								<table>
									<thead>
										<tr>
											<th className="matchday-table__pos">Pl</th>
											<th className="matchday-table__participant">Participants</th>
											<th>M</th>
											<th>G</th>
											<th>GD</th>
											<th className="matchday-table__pts">Pts</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td className="matchday-table__pos">1st</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 1
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">2nd</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 2
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">3rd</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 3
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
										<tr>
											<td className="matchday-table__pos">4th</td>
											<td className="matchday-table__participant">
												<div className="matchday-table__participant-inner">
													<div className="matchday-table__participant-logo-placeholder"></div>
													Team Name 4
												</div>
											</td>
											<td>0</td>
											<td>0:0</td>
											<td>0</td>
											<td className="matchday-table__pts">0</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</>
		);
	},
	save: () => null, // Dynamic block
});
