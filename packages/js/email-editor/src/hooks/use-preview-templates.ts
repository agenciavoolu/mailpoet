import { BlockInstance, parse } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { storeName, EmailTemplatePreview, TemplatePreview } from '../store';

/**
 * We need to merge pattern blocks and template blocks for BlockPreview component.
 * @param templateBlocks - Parsed template blocks
 * @param innerBlocks    - Blocks to be set as content blocks for the template preview
 */
function setPostContentInnerBlocks(
	templateBlocks: BlockInstance[],
	innerBlocks: BlockInstance[]
): BlockInstance[] {
	return templateBlocks.map( ( block: BlockInstance ) => {
		if ( block.name === 'core/post-content' ) {
			return {
				...block,
				name: 'core/group', // Change the name to group to render the innerBlocks
				innerBlocks,
			};
		}
		if ( block.innerBlocks?.length ) {
			return {
				...block,
				innerBlocks: setPostContentInnerBlocks(
					block.innerBlocks,
					innerBlocks
				),
			};
		}
		return block;
	} );
}

export function usePreviewTemplates(): TemplatePreview[][] {
	const { templates, patterns } = useSelect( ( select ) => {
		const contentBlockId =
			// @ts-expect-error getBlocksByName is not defined in types
			select( blockEditorStore ).getBlocksByName(
				'core/post-content'
			)?.[ 0 ];
		return {
			templates: select( storeName ).getEmailTemplates(),
			patterns:
				// @ts-expect-error getPatternsByBlockTypes is not defined in types
				select( blockEditorStore ).getPatternsByBlockTypes(
					[ 'core/post-content' ],
					contentBlockId
				),
		};
	}, [] );

	if ( ! templates || ! patterns.length ) {
		return [ [] ];
	}

	// Pick first pattern that comes from mailpoet and is for general email template
	const contentPatternBlocksGeneral = patterns.find(
		( pattern ) =>
			// eslint-disable-next-line @typescript-eslint/no-unsafe-return
			pattern?.templateTypes?.includes( 'email-general-template' )
	)?.blocks as BlockInstance[];

	// Pick first pattern that comes from mailpoet and is for template with header and footer content separated
	const contentPatternBlocks = patterns.find(
		( pattern ) =>
			// eslint-disable-next-line @typescript-eslint/no-unsafe-return
			pattern?.templateTypes?.includes( 'email-template' )
	)?.blocks as BlockInstance[];

	return [
		templates.map( ( template: EmailTemplatePreview ): TemplatePreview => {
			let parsedTemplate = parse( template.content?.raw );
			parsedTemplate = setPostContentInnerBlocks(
				parsedTemplate,
				template.slug === 'email-general'
					? contentPatternBlocksGeneral
					: contentPatternBlocks
			);

			return {
				slug: template.slug,
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				contentParsed: parsedTemplate,
				patternParsed:
					template.slug === 'email-general'
						? contentPatternBlocksGeneral
						: contentPatternBlocks,
				template,
			};
		} ),
	];
}
