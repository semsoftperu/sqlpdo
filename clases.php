<?php
error_reporting(E_ALL ^E_NOTICE ^E_WARNING ^E_STRICT);
if(!defined('def_aqpweb')){die();}
class mysql {
    var 
		$conexion, $error, $sql_query, $sqlTarea, $sqlTotal, $sqlDriver;
	var
		$fechmode = PDO::FETCH_ASSOC;
    function Connect($driver, $user, $pass){
		$options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION SQL_BIG_SELECTS=1'];	
		try {
		    $this->conexion = new PDO($driver, $user, $pass, $options);
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
		}	
        return $this->conexion;
    }
	function SetFetchMode($tipo){
		//FETCH_ASSOC o 	FETCH_NUM
		$this->fechmode = $tipo;
	}
	function Execute($sql,$array = NULL){
		$this->sqlTarea = strtoupper(substr(trim($sql), 0, 6));
		$this->sqlDriver = $this->conexion->getAttribute(PDO::ATTR_DRIVER_NAME); 
		
		if ($this->sqlTarea == 'SELECT' && $this->sqlDriver == 'sqlite'){
			$parts = explode('FROM',$sql);
			$contar = $this->conexion->prepare('SELECT COUNT(*) as total FROM '.$parts[1]);
			$retcontar = $contar->execute($array);			
			$total = $contar->fetch();
			$selCount = $total['total'];
			$this->sqlTotal = $selCount;	
		}		
		$consulta = $this->conexion->prepare($sql);
		$consulta->setFetchMode($this->fechmode);
		$retorna = $consulta->execute($array);

		if(!$retorna){
			$errorinfo = $consulta->errorInfo();
			$this->error = $errorinfo[2];
		}else{
			$this->sql_query = $consulta;
		}
		return $retorna;
	}  
	function txtError(){
		return $this->error;
	}	
	function fetchrow(){
		return $this->sql_query->fetch();
	}
	function numrows(){
		if ($this->sqlTarea == 'SELECT' && $this->sqlDriver == 'sqlite'){
			$selCount = $this->sqlTotal;
		}else{
			$selCount = $this->sql_query->rowCount();
		}
		return $selCount;		
		
		//return $this->sqlDriver == 'sqlite' ? count($this->sql_query->fetchAll(PDO::FETCH_ASSOC)) : $this->sql_query->rowCount();
	}
	function GetArray(){
		return $this->sql_query->fetchAll();
	}
	function getNewId(){
		return $this->conexion->lastInsertId();
	}

}
class ws_html {
   var 
	$jscript, $tagMeta, $rel, $cscript, $page, $jsonld;
	
	function set_jsonld($campo, $valor){
		$this->jsonld[$campo] = $valor;
	}
	function get_jsonld(){
		$this->set_jsonld("headline", $this->get_page('title'));
		$this->set_jsonld("description", $this->get_page('description'));
		$this->set_jsonld("image", array($this->get_page('cover')));
		$this->set_jsonld("author", array(
			"@type"=> "Person",
			"name"=> $this->get_page('autor')
		));		
		$this->set_jsonld("publisher", array(
			"@type"=> "Organization",
			"name"=> def_web_title,
			"logo" => array(
				'@type' => 'ImageObject',
				'url'=>$this->get_page('cover')
			)));
			
		$this->set_jsonld("mainEntityOfPage", array(
			"@type"=> "WebPage",
			"@id"=> $this->get_page('url')
		));

		
		$html = '<script type="application/ld+json">'.json_encode($this->jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).'</script>';
		return $html;
	}
	function set_meta($metaname, $content, $tipo = 'name'){
		//$this->tagMeta[$tipo.$metaname] = $tipo.tagMeta;
		$this->tagMeta[$tipo.$metaname]['content'] = $content;
		$this->tagMeta[$tipo.$metaname]['name'] = $metaname;
		$this->tagMeta[$tipo.$metaname]['tipo'] = $tipo;
	}	
	function get_meta($tipo = 'html', $metaname ){
		
		$this->set_meta('name', $this->get_page('title'),'itemprop');
		$this->set_meta('description', $this->get_page('description'),'itemprop');
		$this->set_meta('image', $this->get_page('cover'),'itemprop');
		
		$this->set_meta('description', $this->get_page('description'));
		$this->set_meta('Author', $this->get_page('autor'));
		$this->set_meta('category', $this->get_page('category'));
		$this->set_meta('keywords', $this->get_page('keywords'));
	
		$this->set_meta('og:title', $this->get_page('title'),'property');
		$this->set_meta('og:description', $this->get_page('description'),'property');
		$this->set_meta('og:url', $this->get_page('url'),'property');
		$this->set_meta('og:image', $this->get_page('cover'), 'property');	
		
		$this->set_meta('twitter:title',$this->get_page('title'));
		$this->set_meta('twitter:description',$this->get_page('description'));
		$this->set_meta('twitter:image:src', $this->get_page('cover'));	
		$this->set_rel('canonical',$this->get_page('url'));
		if ($tipo == 'html'){
			$html = '';
			ksort($this->tagMeta);
			foreach($this->tagMeta as $i => $value){
					$html .= '<meta '.$value['tipo'].'="'.$value['name'].'" content="'.$value['content'].'">'.PHP_EOL;
			}				
			return $html;		
		}else{
			return $this->tagMeta[$tipo.$metaname]['content'];
		}
	}
	
	function set_js($link, $attr = '', $head = true){
		$this->jscript[$link]['attr'] = $attr;
		$this->jscript[$link]['mode'] = $head;
	}
	function get_js($head = true){
		$out = '';
		foreach($this->jscript as $i => $value){
			if ($value['mode'] == $head){
				$out .= '<script '.$value['attr'].' language="javascript" src="'.$i.'" type="text/javascript"></script>'.PHP_EOL;
			}
		}				
		
		return $out;		
	}
	
	function set_rel($relacion, $url){
		$this->rel[$relacion] = $url;
	}	
	function get_rel(){
		$relaciones = '';
		foreach($this->rel as $name => $url){
			$relaciones .= '<link rel="'.$name.'" href="'.$url.'" />'.PHP_EOL;
		}
		return $relaciones;
	}	
	
	function set_css($link){
		$this->cscript[$link] = $link;
	}	
	function get_css(){
		foreach($this->cscript as $i => $value){
			$valor .= '<link href="'.$value.'" rel="stylesheet" type="text/css" />'.PHP_EOL;			
		}

		return $valor;
	}

	function get_head_all(){
		global $ldJson;
		$out = '<title>'.$this->get_page('title').'</title>' . PHP_EOL . 
		$this->get_meta('html','').
		$this->get_css().
		$this->get_rel().
		$this->get_js().
		$this->get_jsonld(). PHP_EOL ;
		return $out;
	}
	function get_foot_all(){
		$out = $this->get_js(false);
		return $out;
	}	
	function set_page($div = 'page', $html){
		switch ($div){
			case 'title': case 'description': case 'cover': case 'autor': case 'url': case 'category': case 'keywords':
				$this->page[$div] = $html;
			break;
			default:
				$this->page[$div] .= $html;
			break;
		}
	}
	function get_page($div = 'page'){
		$out = '';
		$out .= $this->page[$div];
		return $out;
	}	
	
}
class thumb { 
    
   var $image; 
   var $type; 
   var $width; 
   var $height;
   var $propiedades; 
    
   //---Método de leer la imagen 
   function loadImage($name) { 
       
      //---Tomar las dimensiones de la imagen 
      $info = @getimagesize($name); 
       
      $this->width = $info[0]; 
      $this->height = $info[1]; 
      $this->type = $info[2];  
	  $this->propiedades = $info;;  
       
      //---Dependiendo del tipo de imagen crear una nueva imagen 
      switch($this->type){         
         case IMAGETYPE_JPEG: 
            $this->image = imagecreatefromjpeg($name); 
         break;         
         case IMAGETYPE_GIF: 
            $this->image = imagecreatefromgif($name); 
         break;         
         case IMAGETYPE_PNG: 
            $this->image = imagecreatefrompng($name); 
         break;         
      }      
   } 
   function tipo($propiedad){
	 return   $this->propiedades[$propiedad];
   }
    
   //---Método de guardar la imagen 
   function save($name, $quality = 100) { 
       
      //---Guardar la imagen en el tipo de archivo correcto 
      switch($this->type){         
         case IMAGETYPE_JPEG: 
            imagejpeg($this->image, $name, $quality); 
         break;         
         case IMAGETYPE_GIF: 
             imagegif($this->image, $name); 
         break;         
         case IMAGETYPE_PNG: 
            $pngquality = floor(($quality - 10) / 10); 
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
            imagepng($this->image, $name, $pngquality); 
			
         break;         
      }
     imagedestroy($this->image);
   } 
    
   //---Método de mostrar la imagen sin salvarla 
   function show() { 
       
      //---Mostrar la imagen dependiendo del tipo de archivo 
      switch($this->type){         
         case IMAGETYPE_JPEG: 
            imagejpeg($this->image); 
         break;         
         case IMAGETYPE_GIF: 
            imagegif($this->image); 
         break;         
         case IMAGETYPE_PNG: 
            imagepng($this->image); 
         break;
      }
     imagedestroy($this->image);
   } 
    
   //---Método de redimensionar la imagen sin deformarla 
   function resize($value, $prop){ 
       
      //---Determinar la propiedad a redimensionar y la propiedad opuesta 
      $prop_value = ($prop == 'width') ? $this->width : $this->height; 
      $prop_versus = ($prop == 'width') ? $this->height : $this->width; 
       
      //---Determinar el valor opuesto a la propiedad a redimensionar 
      $pcent = $value / $prop_value;       
      $value_versus = $prop_versus * $pcent; 
       
      //---Crear la imagen dependiendo de la propiedad a variar 
      $image = ($prop == 'width') ? imagecreatetruecolor($value, $value_versus) : imagecreatetruecolor($value_versus, $value); 
       
      //---Hacer una copia de la imagen dependiendo de la propiedad a variar 
      switch($prop){ 
          
         case 'width': 
            imagecopyresampled($image, $this->image, 0, 0, 0, 0, $value, $value_versus, $this->width, $this->height); 
         break; 
          
         case 'height': 
            imagecopyresampled($image, $this->image, 0, 0, 0, 0, $value_versus, $value, $this->width, $this->height); 
         break; 
          
      } 
       
      //---Actualizar la imagen y sus dimensiones 
      //$info = getimagesize($image); 
      $this->width = imagesx($image); 
      $this->height = imagesy($image); 
      $this->image = $image; 
       
   }    
    
   //---Método de extraer una sección de la imagen sin deformarla 
   function crop($cwidth, $cheight, $pos = 'center') { 
       
      //---Hallar los valores a redimensionar
      $new_w = $cwidth;
      $new_h = ($cwidth / $this->width) * $this->height;
      
      //---Si la altura es menor recalcular por la altura
      if($new_h < $cheight){
         
         $new_h = $cheight;
         $new_w = ($cheight / $this->height) * $this->width;
      
      }
      
      $this->resize($new_w, 'width');
       
      //---Crear la imagen tomando la porción del centro de la imagen redimensionada con las dimensiones deseadas 
      $image = imagecreatetruecolor($cwidth, $cheight); 
       
      switch($pos){ 
          
         case 'center': 
            imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $cwidth) / 2), abs(($this->height - $cheight) / 2), $cwidth, $cheight, $cwidth, $cheight); 
         break; 
          
         case 'left': 
            imagecopyresampled($image, $this->image, 0, 0, 0, abs(($this->height - $cheight) / 2), $cwidth, $cheight, $cwidth, $cheight); 
         break; 
          
         case 'right': 
            imagecopyresampled($image, $this->image, 0, 0, $this->width - $cwidth, abs(($this->height - $cheight) / 2), $cwidth, $cheight, $cwidth, $cheight); 
         break; 
          
         case 'top': 
            imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $cwidth) / 2), 0, $cwidth, $cheight, $cwidth, $cheight); 
         break; 
          
         case 'bottom': 
            imagecopyresampled($image, $this->image, 0, 0, abs(($this->width - $cwidth) / 2), $this->height - $cheight, $cwidth, $cheight, $cwidth, $cheight); 
         break; 
       
      } 
       
      $this->image = $image; 
   } 
    
} 