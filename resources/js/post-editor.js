import tinymce from 'tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/skins/ui/oxide/skin.css';

export const initializePostEditor = (editors) => {
    editors.forEach((element) => {
        tinymce.init({
            target: element,
            license_key: 'gpl',
            menubar: false,
            promotion: false,
            branding: false,
            height: 520,
            resize: true,
            relative_urls: false,
            document_base_url: `${window.location.origin}/`,
            plugins: 'lists link image code codesample',
            toolbar: 'blocks | bold italic | bullist numlist | blockquote codesample | link image media_library | code',
            codesample_languages: [
                { text: 'HTML/XML', value: 'markup' },
                { text: 'JavaScript', value: 'javascript' },
                { text: 'CSS', value: 'css' },
                { text: 'PHP', value: 'php' },
                { text: 'Ruby', value: 'ruby' },
                { text: 'Python', value: 'python' },
                { text: 'Java', value: 'java' },
                { text: 'C', value: 'c' },
                { text: 'C#', value: 'csharp' },
                { text: 'C++', value: 'cpp' },
                { text: 'Bash / Shell', value: 'bash' },
            ],
            block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre',
            toolbar_mode: 'sliding',
            setup: (editor) => {
                editor.ui.registry.addButton('media_library', {
                    text: 'Media library',
                    onAction: () => {
                        element.closest('form')?.dispatchEvent(new CustomEvent('media-gallery:open', {
                            detail: {
                                onSelect: (media) => {
                                    const image = document.createElement('img');
                                    image.src = media.url;
                                    image.alt = media.alt_text || media.name;
                                    editor.insertContent(image.outerHTML);
                                },
                            },
                        }));
                    },
                });
            },
            skin: false,
            content_css: false,
            content_style: 'body { max-width: 72ch; margin: 1.5rem auto; padding: 0 1rem; color: #292524; font-family: ui-sans-serif, system-ui, sans-serif; font-size: 16px; line-height: 1.8; } h2, h3, h4 { color: #0c0a09; font-weight: 700; line-height: 1.25; } blockquote { border-left: 4px solid #047857; margin-left: 0; padding-left: 1rem; color: #57534e; } pre { overflow-x: auto; border-radius: 0.75rem; background: #0b1020; padding: 1rem; color: #e8edff; font-size: 17px; line-height: 1; tab-size: 2; } .token.comment, .token.prolog, .token.doctype, .token.cdata { color: #8b96b8; } .token.punctuation { color: #c5cde3; } .token.property, .token.tag, .token.boolean, .token.number, .token.constant, .token.symbol, .token.attr-name { color: #ffb86c; } .token.selector, .token.string, .token.char, .token.attr-value, .token.builtin, .token.inserted { color: #a5e075; } .token.operator, .token.entity, .token.url { color: #78dce8; } .token.keyword, .token.deleted { color: #ff79c6; } .token.function, .token.class-name { color: #82aaff; } .token.variable { color: #f8f8f2; } a { color: #166534; text-decoration: underline; } img { height: auto; max-width: 100%; } @media (min-width: 768px) { body { max-width: none; padding: 0 0.5rem; } }',
            images_upload_handler: async (blobInfo, progress) => {
                const formData = new FormData();
                formData.append('image', blobInfo.blob(), blobInfo.filename());

                const response = await fetch(element.dataset.uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                if (!response.ok) {
                    const result = await response.json().catch(() => ({}));
                    const validationErrors = Object.values(result.errors ?? {}).flat();
                    const message = validationErrors.join(' ') || result.message || 'The image upload failed.';

                    throw new Error(message);
                }

                const result = await response.json();

                if (typeof result.location !== 'string') {
                    throw new Error('The image upload response did not include a location.');
                }

                progress(100);

                return result.location;
            },
        });
    });
};
