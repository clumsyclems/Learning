const { registerBlockType } = wp.blocks;
const { RichText, InspectorControls, ColorPalette, MediaUploadCheck, MediaUpload } = wp.editor;
const { PanelBody, IconButton, } = wp.components;

registerBlockType('crodriguez/custom-cta', {
    
    // built-in attributes

    title: 'Call to Action',

    description: 'Block to generate a custom Call to Action',

    icon: 'format-image',

    category: 'layout',

    // custom attributes

    attributes: {

        author: {

            type: 'string',
        },
        
        title: {

            type: 'string',

            source: 'html',

            selector: 'h2'
        },

        titleColor: {
            string: 'string',

            default: 'black'
        },

        body: {

            type: 'string',

            source: 'html',

            selector: 'p'

        },

        backgroundImage: {

            type: 'string',

            default: null
        }
    },

    // custom functions

    // built-in functions

    edit( { attributes, setAttributes }) {

        //to write directly in the editor
        /*
        function updateAuthor(newAuthor){

            setAttributes( {author: newAuthor.target.value})
        }


        return <input value= { attributes.author } onChange= { updateAuthor } type="text"/> ;
        */

        //to create a Rich text Markup element 

        const { title, body, titleColor, backgroundImage} = attributes;

        function onChangeTitle(newTitle){

            setAttributes( { title: newTitle})
        }

        function onChangeBody(newBody){

            setAttributes( { body: newBody})
        }

        function onTitleColorChange(newTitleColor) {

            setAttributes( { titleColor: newTitleColor})

        }

        function onSelectImage(newImage) {

            setAttributes( { backgroundImage: newImage.sizes.full.url })

        }

        return ([
            
            <InspectorControls style={{ marginBottom: '39px' }}>
                <PanelBody title={'Font Color Setting'}>

                    <p><strong>Select Title Color:</strong></p>

                    <ColorPalette value={ titleColor }
                                  onChange={ onTitleColorChange } />

                </PanelBody>


                <PanelBody title= { 'Background Image Settings'}>

                    <p><strong>Select Background Image:</strong></p>
                    <MediaUploadCheck>
		                    <MediaUpload
		                    	onSelect={ onSelectImage }
		                    	type="image"
		                    	value={ backgroundImage }
		                    	render={ ( { open } ) => (
		                    		<IconButton onClick={ open } 
                                                icon="upload" 
                                                className="editor-media-placeholder__button is-button is-default is-large"
                                                >Background Image</IconButton>
		                    	) }
		                    />
		            </MediaUploadCheck>
                </PanelBody>

            </InspectorControls>

            ,

            <div class="cta-container" style={{ 
                backgroundImage: `url(${backgroundImage})`, 
                backgroundSize: 'cover', 
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat'
                }}>

                    <RichText key="editable"
                        tagName="h2"
                        placeholder="Your CTA Title"
                        value={title}
                        onChange={onChangeTitle}
                        style={{ color: titleColor }} />

                    <RichText key="editable"
                        tagName="p"
                        placeholder="Your CTA Description"
                        value={body}
                        onChange={onChangeBody} />

            </div>

        ]);

    }
    
    ,

    save( { attributes }) {

        const { title, body, titleColor, backgroundImage} = attributes;

        return (
            <div class="cta-container"style={{ 
                backgroundImage: `url(${backgroundImage})`, 
                backgroundSize: 'cover', 
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat'
                }}>

                <h2 style={ { color: titleColor } }>{ title }</h2>

                <RichText.Content tagName="p"
                                  value={ body }/>

            </div>
        );

    }

});