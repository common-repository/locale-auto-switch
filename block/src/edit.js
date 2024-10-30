import ServerSideRender from '@wordpress/server-side-render';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<ServerSideRender
				block = 'locale-auto-switch/las-links-block'
			/>
		</div>
	);
}
