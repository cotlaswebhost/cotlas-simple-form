<?php

if (!defined('ABSPATH')) {
    exit;
}

class CSF_EditorJS_Parser {

    public static function to_gutenberg($json){

        $data=json_decode($json,true);

        if(
            !$data ||
            empty($data['blocks'])
        ){
            return $json;
        }

        $content='';

        foreach($data['blocks'] as $block){

            if(empty($block['type'])){
                continue;
            }

            switch($block['type']){


                /*
                ---------------------------------
                PARAGRAPH
                ---------------------------------
                */

                case 'paragraph':

                    $text=wp_kses_post(
                        $block['data']['text'] ?? ''
                    );

                    $content .= '
<!-- wp:paragraph -->
<p>'.$text.'</p>
<!-- /wp:paragraph -->
';

                break;



                /*
                ---------------------------------
                HEADER
                ---------------------------------
                */

                case 'header':

                    $level=intval(
                        $block['data']['level'] ?? 2
                    );

                    $text=wp_kses_post(
                        $block['data']['text'] ?? ''
                    );

                    $content .= '
<!-- wp:heading {"level":'.$level.'} -->
<h'.$level.'>'.$text.'</h'.$level.'>
<!-- /wp:heading -->
';

                break;



                /*
                ---------------------------------
                IMAGE
                ---------------------------------
                */

                case 'image':

                    $url = esc_url(
                        $block['data']['file']['url'] ?? ''
                    );

                    $caption = wp_kses_post(
                        $block['data']['caption'] ?? ''
                    );

                    $withBorder = !empty(
                        $block['data']['withBorder']
                    );

                    $withBackground = !empty(
                        $block['data']['withBackground']
                    );

                    $stretched = !empty(
                        $block['data']['stretched']
                    );


                    /*
                    Default classes
                    */

                    $classes = array(
                        'wp-block-image'
                    );


                    /*
                    Gutenberg image attributes
                    */

                    $image_block_attrs='';


                    /*
                    With border
                    */

                    if($withBorder){

                        $classes[]=
                        'csf-image-border';

                    }


                    /*
                    With background
                    */

                    if($withBackground){

                        $classes[]=
                        'csf-image-background';

                    }


                    /*
                    Stretch image
                    */

                    if($stretched){

                        /*
                        Native Gutenberg
                        wide alignment
                        */

                        $classes[]=
                        'alignwide';

                        $image_block_attrs=
                        '{"align":"wide"}';

                    }


                    $classString=
                    implode(
                        ' ',
                        $classes
                    );


                    $content .= '

                <!-- wp:image '.$image_block_attrs.' -->

                <figure class="'.$classString.'">

                <img
                src="'.$url.'"
                decoding="async"
                />

                '.(

                $caption

                ?

                '<figcaption>'
                .$caption.
                '</figcaption>'

                :

                ''

                ).'

                </figure>

                <!-- /wp:image -->

                ';

                break;                




                /*
                ---------------------------------
                LIST
                ---------------------------------
                */

                case 'list':

                    $style=
                    $block['data']['style'] ??
                    'unordered';

                    $items=
                    $block['data']['items'] ?? [];

                    $html='';

                    foreach($items as $item){

                        $html.=
                        '<li>'.
                        wp_kses_post($item).
                        '</li>';

                    }

                    if(
                        $style==='ordered'
                    ){

                        $content.='
<!-- wp:list {"ordered":true} -->
<ol>
'.$html.'
</ol>
<!-- /wp:list -->
';

                    }else{

                        $content.='
<!-- wp:list -->
<ul>
'.$html.'
</ul>
<!-- /wp:list -->
';

                    }

                break;




                /*
                ---------------------------------
                CHECKLIST
                ---------------------------------
                */

                case 'checklist':

                    $items=
                    $block['data']['items'] ?? [];

                    $html='';

                    foreach(
                        $items as $item
                    ){

                        $checked=
                        !empty(
                        $item['checked']
                        );

                        $text=
                        wp_kses_post(
                        $item['text']
                        );

                        $html.=
                        '<li>'.
                        ($checked?'✓ ':'').
                        $text.
                        '</li>';

                    }

                    $content.='
<!-- wp:list -->
<ul>
'.$html.'
</ul>
<!-- /wp:list -->
';

                break;




                /*
                ---------------------------------
                QUOTE
                ---------------------------------
                */

                case 'quote':

                    $text=
                    wp_kses_post(
                    $block['data']['text'] ?? ''
                    );

                    $caption=
                    wp_kses_post(
                    $block['data']['caption'] ?? ''
                    );

                    $content.='
<!-- wp:quote -->
<blockquote>

<p>
'.$text.'
</p>

'.(
$caption
?
'<cite>'.$caption.'</cite>'
:
''
).'

</blockquote>
<!-- /wp:quote -->
';

                break;




                /*
                ---------------------------------
                TABLE
                ---------------------------------
                */

                case 'table':

                    $rows=
                    $block['data']['content']
                    ?? [];

                    $table='';

                    foreach(
                        $rows as $row
                    ){

                        $table.='<tr>';

                        foreach(
                            $row as $col
                        ){

                            $table.=
                            '<td>'.
                            wp_kses_post($col).
                            '</td>';

                        }

                        $table.='</tr>';

                    }

                    $content.='
<!-- wp:table -->
<figure class="wp-block-table">
<table>
<tbody>
'.$table.'
</tbody>
</table>
</figure>
<!-- /wp:table -->
';

                break;




                /*
                ---------------------------------
                CODE
                ---------------------------------
                */

                case 'code':

                    $code=
                    esc_html(
                    $block['data']['code']
                    ?? ''
                    );

                    $content.='
<!-- wp:code -->
<pre class="wp-block-code">
<code>'.$code.'</code>
</pre>
<!-- /wp:code -->
';

                break;




                /*
                ---------------------------------
                DELIMITER
                ---------------------------------
                */

                case 'delimiter':

                    $content.='
<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->
';

                break;





                /*
                ---------------------------------
                FALLBACK
                ---------------------------------
                */

                default:

                    if(
                    !empty(
                    $block['data']
                    )
                    ){

                        $content.='
<!-- wp:paragraph -->
<p>
Unsupported EditorJS block:
'.esc_html(
$block['type']
).'
</p>
<!-- /wp:paragraph -->
';

                    }

                break;

            }

        }

        return $content;

    }
    public static function gutenberg_to_editorjs(
        $content
    ){

        $blocks = parse_blocks(
            $content
        );

        $editor_blocks=[];

        foreach(
            $blocks as $block
        ){

            switch(
                $block['blockName']
            ){

                case 'core/paragraph':

                    $editor_blocks[]=[

                        'type'=>'paragraph',

                        'data'=>[
                            'text'=>wp_strip_all_tags(
                                $block['innerHTML']
                            )
                        ]

                    ];

                break;


                case 'core/heading':

                    preg_match(
                        '/<h([1-6])/',
                        $block['innerHTML'],
                        $matches
                    );

                    $level=
                    $matches[1] ?? 2;

                    $editor_blocks[]=[

                        'type'=>'header',

                        'data'=>[

                            'text'=>
                            wp_strip_all_tags(
                                $block['innerHTML']
                            ),

                            'level'=>
                            intval(
                                $level
                            )

                        ]

                    ];

                break;


                case 'core/image':

                    preg_match(
                        '/src="([^"]+)"/',
                        $block['innerHTML'],
                        $matches
                    );

                    $url=
                    $matches[1] ?? '';

                    $editor_blocks[]=[

                        'type'=>'image',

                        'data'=>[

                            'file'=>[
                                'url'=>$url
                            ],

                            'caption'=>'',

                            'withBorder'=>false,

                            'withBackground'=>false,

                            'stretched'=>false

                        ]

                    ];

                break;

            }

        }

        return wp_json_encode([

            'time'=>time(),

            'blocks'=>$editor_blocks,

            'version'=>'2.30.0'

        ]);

    }

}
