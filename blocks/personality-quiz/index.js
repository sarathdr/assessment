import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

registerBlockType( 'personality-assessment/quiz', {
	title: __( 'Personality Quiz', 'personality-assessment' ),
	icon: 'forms',
	category: 'widgets',
	attributes: {
		quizId: {
			type: 'string',
		},
		showTitle: {
			type: 'boolean',
			default: true,
		},
		showProgress: {
			type: 'boolean',
			default: true,
		},
	},
	edit: withSelect( ( select ) => ( {
		quizzes: select( 'core' ).apiFetch( { path: '/pa/v1/quizzes' } ),
	} ) )( ( { quizzes, attributes, setAttributes } ) => {
		const quizOptions = quizzes
			? quizzes.map( ( quiz ) => ( {
					label: quiz.title,
					value: quiz.id,
			  } ) )
			: [];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Quiz Settings', 'personality-assessment' ) }>
						<SelectControl
							label={ __( 'Quiz', 'personality-assessment' ) }
							value={ attributes.quizId }
							options={ [
								{ label: __( 'Select a Quiz', 'personality-assessment' ), value: '' },
								...quizOptions,
							] }
							onChange={ ( quizId ) => setAttributes( { quizId } ) }
						/>
						<ToggleControl
							label={ __( 'Show Title', 'personality-assessment' ) }
							checked={ attributes.showTitle }
							onChange={ ( showTitle ) => setAttributes( { showTitle } ) }
						/>
						<ToggleControl
							label={ __( 'Show Progress Bar', 'personality-assessment' ) }
							checked={ attributes.showProgress }
							onChange={ ( showProgress ) => setAttributes( { showProgress } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div>{ __( 'Personality Quiz', 'personality-assessment' ) }</div>
			</>
		);
	} ),
	save: () => {
		return null;
	},
} );
