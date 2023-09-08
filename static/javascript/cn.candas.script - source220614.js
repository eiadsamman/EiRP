class Point{
	constructor(x=0,y=0){
		this.X=x;
		this.Y=y;
	}
}
class Boundary{
	constructor(){
		this.left=0;
		this.right=0;
		this.top=0;
		this.left=0;
	}
}

let Candas = function(){
	/*(Check if @value is a number (int or float) @returns bool)*/
	this.isNumber = function isNumber(value) {
		return typeof value === 'number' && isFinite(value);
	}
	/*(Force only float and integer number on input fields)*/
	/*(This sould be trigged for all input events (input keydown keyup mousedown mouseup select contextmenu drop) )*/
	this.OnlyFloat=function(obj,uLimit=null,lLimit=null){
		if (/^-?\d*[.,]?\d*$/.test(obj.value)) {
			if(uLimit!=null && obj.value > uLimit){obj.value=uLimit;}
			if(lLimit!=null && parseFloat(obj.value) < lLimit){obj.value=lLimit;}
			obj.oldValue = obj.value;
			obj.oldSelectionStart = obj.selectionStart;
			obj.oldSelectionEnd = obj.selectionEnd;
		} else if (obj.hasOwnProperty("oldValue")) {
			obj.value = obj.oldValue;
			obj.setSelectionRange(obj.oldSelectionStart, obj.oldSelectionEnd);
		} else {
			obj.value = "";
		}
	}
	return this;
}
candas = new Candas();

/*(Use Candas class as a wrapper)*/
(function(CX){
	CX.Pyhsics = function(options){
		var defaults = {
				onupdate: function(){},
				onload: function(){},
				centroid_axis_color:"#0066cc",
				boundary_axis_color:"#ff0033",
				world_cords_color:"#bbbbbb",
				color_threshold: 130,
				color_invert : false,
				plotter_width: 400
			};
		var settings = {
			...defaults,
			...options
			};
			
		var _this 			= this,
			gdImage 		= new Image,
			ready_status 	= false,

			/*(Canvas for image raw source)*/
			cs 				= document.createElement("canvas"),
			cs_cxt 			= cs.getContext('2d'),
			cs_imgdata 		= null,
			
			/*(Canvas for resized image)*/
			rs 				= document.createElement("canvas"),
			rs_cxt 			= cs.getContext('2d'),
			
			/*(Interface canvas)*/
			cp 				= null,
			cp_cxt 			= null,
			
			fileReader 		= new FileReader(),
			dim_imgsrc 		= new Point(),
			dim_plotsize	= new Point(),
			scl_ratio		= new Point();
		
		/*(Private data parameters)*/
		/*(Uses `properties set function` to change)*/
		let params 	= {
			pipe_diameter: 2500,
			profile_thickness: 1,
			profile_width: 120,
			data_youngmod_short: 800,
			data_material_density: 0.949
			}
		
		/*(Accept image from local uploads)*/
		gdImage.crossOrigin = "Anonymous";
		
		
		/*
		 * Draw world X&Y coordinates axis on the top left on the output canvas
		*/
		function DrawCords(cp_cxt, height){
			cp_cxt.translate(0.5, 0.5);
			cp_cxt.beginPath();
			cp_cxt.lineWidth = 1;
			cp_cxt.strokeStyle = settings.world_cords_color;
			cp_cxt.moveTo(10, height - 10);
			cp_cxt.lineTo(50, height - 10);
			cp_cxt.moveTo(10, height - 10);
			cp_cxt.lineTo(10, height - 50);
			cp_cxt.stroke();
			
			cp_cxt.font = "12px Arial";
			cp_cxt.fillStyle = settings.world_cords_color;
			cp_cxt.fillText("X", 55, height - 7); 
			cp_cxt.fillText("Y", 7, height - 55);
			
			cp_cxt.translate(-0.5, -0.5);
			}
		
		
		/*
		 * Initialize processing canvas
		 * Prepare the following canvases for processing
		 * cs main canvas that contains the image srouce gdImage
		 * cp the interface canvas that contains the UI output image
		 * rs the resized image
		 * 
		 * @2022-05-07
		 * @function imageReceived
		 * @param null
		 * @returns void
		 * 
		*/
		function imageReceived () {
			/*(Set trigger to on for future processing )*/
			ready_status	= true;
			/*(Main image dimensions)*/
			dim_imgsrc.X 	= gdImage.width;
			dim_imgsrc.Y 	= gdImage.height;
			
			/*(Resize main canvas `cs`)*/
			cs.width = dim_plotsize.X = dim_imgsrc.X;
			cs.height = dim_plotsize.Y = dim_imgsrc.Y;
			/*(Draw image to canvas)*/
			cs_cxt.drawImage(gdImage, 0, 0);
			/*(Get image data)*/
			cs_imgdata = cs_cxt.getImageData(0, 0, dim_imgsrc.X, dim_imgsrc.Y);
			
			
			/*(Clear interface canvas `cp`)*/
			cp_cxt.clearRect(0, 0, dim_imgsrc.X, dim_imgsrc.Y);
			
			/*(Scale down over sized images based on desired width)*/
			if (dim_imgsrc.X > settings.plotter_width) {
				/*(Scale Y to preserv aspect ratio)*/
				dim_plotsize.Y *= settings.plotter_width / dim_imgsrc.X;
				/*(Scale X)*/
				dim_plotsize.X = settings.plotter_width;
				
				/*(Save scalling ratio for future calculations)*/
				scl_ratio.X = dim_plotsize.X / dim_imgsrc.X;
				scl_ratio.Y = dim_plotsize.Y / dim_imgsrc.Y;
				
				/*()*/
				cp.width = rs.width = dim_plotsize.X;
				cp.height = rs.height = dim_plotsize.Y;
			
			/*(Leave smaller image as it is)*/
			}else{
				scl_ratio.X = scl_ratio.Y = 1;
				cp.width = rs.width = dim_imgsrc.X;
				cp.height = rs.height = dim_imgsrc.Y;
			}
			
			/*(Scale image for visual presentation)*/
			rs_cxt.drawImage(gdImage, 0, 0, rs.width, rs.height);
			
			/*(Trigger `onload` function )*/
			settings.onload.call(this, {
				'inputsize': dim_imgsrc
			});
			/*(Start processing )*/
			imageProcess();
			}
		
		/*
		 * Calculate Lxx & Lyy from given pixels density array and centroid
		 * 
		 * 
		 * @2022-05-07
		 * @function MomentOfInertia
		 * @param {Array} pixel_density: array contains pixels count 
		 * @param {int} centroid: number indicating the center of mass of the given arrany
		 * @returns {float} : output moment of inertia
		 * 
		*/
		function MomentOfInertia(pixels_density, centroid){
			let moi 		= Number(0);
			let r 			= 0;
			
			if(centroid > 0 && pixels_density.length > 0){
				for(let i=centroid; i>=0; i--){
					moi += params.profile_thickness * pixels_density[i] * r**2; 
					r++;
				}
				r=0;
				for(let i=centroid; i< pixels_density.length; i++){
					moi+= params.profile_thickness * pixels_density[i] * r**2 ;
					r++;
				}
			}else{
				moi = 0;
			}
			return moi;
			}
			
		/*
		 * Process input image, use main canvas `cs` loaded with `gdImage` image as a source
		 * Determine input centroid and boundaries based on `settings.color_threshold`
		 * 
		 * Build canvas `cp` croped to original image boundaries
		 * This will be used in a third canvas to build a scaled image of the acutal image found from the source
		 * 
		 * Build the extraced image between image boundaries and scaled to given profile with `params[profile_width]`
		 *  to `bn` canvas
		 * Usd `bn_cxt` canvas to calculate the area, centroid, moi, mass,...
		 * 
		 * @2022-05-07
		 * @function imageProcess
		 * @param null
		 * @returns void
		 *  
		*/
		function imageProcess () {
			/*(No input provided)*/
			if(!ready_status){return false;}
			
			/*(Init)*/
			let rigidbody_centroid 	= new Point();
			let dim_bound			= new Boundary();
			let source_area			= 0;
			let source_centroid		= new Point();
			let bn 					= document.createElement("canvas");
			let bn_cxt 				= bn.getContext('2d');
			let bn_imgdata 			= null;
			let num=clravr=i=color_w=color_b=0;

			/*(Rescaled input image)*/
			rs_imgdata = rs_cxt.getImageData(0, 0, rs.width, rs.height);
			
			
			/*(Color control (monocrhome 0 or 255))*/
			color_w = settings.color_invert ? 0 : 255;
			color_b = 255 - color_w;
			
			/*(Reset params)*/
			source_centroid.X = source_centroid.Y = 0;
			dim_bound.left = dim_imgsrc.X;
			dim_bound.top = dim_imgsrc.Y;
			dim_bound.bottom = dim_bound.right = 0;


			/*(Get properties of the original image monochromed source without scaling using `settings.color_threshold` )*/
			/*(Determine image boundaries (X,Y) and `source_centroid`)*/
			for(var y = 0; y < dim_imgsrc.Y; y++){
				for(var x = 0; x < dim_imgsrc.X; x++){
					i = (y * 4) * dim_imgsrc.X + x * 4;
					/*(Average pixel color to get gray scale image)*/
					clravr = (cs_imgdata.data[i] + cs_imgdata.data[i + 1]+ cs_imgdata.data[i + 2])/3;
					/*(Convert to monocrhome)*/
					clravr = clravr < settings.color_threshold ? color_b : color_w;
					/*(Use back color as reference)*/
					if(clravr==0){
						/*(Left boundary (first pixel from the left))*/
						if (x < dim_bound.left)
							dim_bound.left = x;
						/*(Right boundary (last pixel from the right))*/
						if (x > dim_bound.right)
							dim_bound.right = x;
						
						/*(Top boundary (first pixel from the top))*/
						if (y < dim_bound.top)
							dim_bound.top = y;
						/*(Bottom boundary (last pixel from the bottom))*/
						if (y > dim_bound.bottom)
							dim_bound.bottom = y;
						
						/*(Count all pixels on X & Y axis)*/
						source_centroid.X+=x;
						source_centroid.Y+=y;
						/*(Count all pixels)*/
						source_area++;
					}
				}
			}
			/*(No Y Boundary found)*/
			if(dim_bound.left >=dim_bound.right){
				dim_bound.left=0;
				dim_bound.right=0;
			}
			/*(No X Boundary found)*/
			if(dim_bound.top >=dim_bound.bottom){
				dim_bound.top=0;
				dim_bound.bottom=0;
			}
			
			/*(Change output image to monochrome)*/
			/*(Ommit to leave input image as it is)*/
			for (var i = 0; i < rs_imgdata.data.length; i += 4) {
				clravr = (rs_imgdata.data[i] + rs_imgdata.data[i + 1]+ rs_imgdata.data[i + 2])/3;
				clravr = clravr < settings.color_threshold ? color_b : color_w;
				rs_imgdata.data[i] = rs_imgdata.data[i + 1] = rs_imgdata.data[i + 2] = clravr==0 ? 0 : 255;
			}
			
			/*(Calculate image pixels centroid based on pixels density on X & Y Axis)*/
			/*(If no pixels found the centroid is 0x0 otherwise the centroid is the average of X/Y axis pixels)*/
			if(source_area!=0){
				source_centroid.X /= source_area;
				source_centroid.Y /= source_area;
			}else{
				source_centroid.X = 0;
				source_centroid.Y = 0;
			}
			
			/*(Surface area is all accepted pixels count)*/
			
			
			
			/*(Plot output image)*/
			cp_cxt.clearRect(0, 0, cp.width, cp.height);
			cp_cxt.putImageData(rs_imgdata, 0, 0);
		
			
			
			/******
			*******
				Re-calculate profile information based on the given profile witdh
				This image is not visible to the user
				Image is located on `bn` canvas and `bn_cxt` 2d context
			*******
			*******/
			const pixels_density = {X:[],Y:[]}
			num=0;
			
			/*(Set canvas dimensions//)*/
			/*(Resize the width)*/
			bn.width = params.profile_width;
			/*(Preserve aspect ratios for the height)*/
			bn.height = params.profile_width  * (dim_bound.bottom - dim_bound.top) / (dim_bound.right - dim_bound.left);
			
			
			if(bn.width==0 || bn.height==0){
				/*(Resized image is not valud)*/
				num=0;
				rigidbody_centroid.X=0;
				rigidbody_centroid.Y=0;
				
				settings.onupdate.call(this, {
					'source':{'centroid':new Point(0,0),'area':0,'boundary':new Boundary(),},
					'extracted':{'centroid':new Point(0,0),'area':0,'height':0,'moi':{'lxx':0,'lyy':0},'sr': 0,'sn': 0},
					'pipe':{'inner_diameter': 0,'outer_diameter': 0,'mean_radius': 0,'mass':0}
				});
				DrawCords(cp_cxt, cp.height);
			}else{
				/*(Clear the canvas)*/
				bn_cxt.clearRect(0, 0, cp.width, cp.height);
				
				/*(Scale original image `gdImage`)*/
				bn_cxt.drawImage(
						gdImage, 
						dim_bound.left, 
						dim_bound.top, 
						dim_bound.right - dim_bound.left, 
						dim_bound.bottom - dim_bound.top,
						0,
						0,
						bn.width,
						bn.height
						);
				
				/*(Get image date from the canvas)*/
				bn_imgdata = bn_cxt.getImageData(0, 0, bn.width, bn.height);
				
				/*(Create new `0` array with lengths based on scaled image height and width)*/
				/*(This array will be used to calculate the moment of inertia)*/
				pixels_density.X = new Array(bn.width);
				for (let i=0; i<bn.width; ++i) pixels_density.X[i] = 0;
				pixels_density.Y = new Array(bn.height);
				for (let i=0; i<bn.height; ++i) pixels_density.Y[i] = 0;
				
				/*(Walkthrough each pixel)*/
				for(var y = 0; y < bn.height; y++){
					for(var x = 0; x < bn.width; x++){
						i = (y * 4) * bn.width + x * 4;
						clravr = (bn_imgdata.data[i] + bn_imgdata.data[i + 1]+ bn_imgdata.data[i + 2])/3;
						clravr = clravr < settings.color_threshold ? color_b : color_w;
						
						if(clravr==0){
							/*(Scaled image centroid)*/
							rigidbody_centroid.X+=x;
							rigidbody_centroid.Y+=y;
							/*(Scaled image area)*/
							num++;
							/*(Scaled image moment of inertia)*/
							pixels_density.X[x]+=1;
							pixels_density.Y[y]+=1;
						}
					}
				}
				
				if(num!=0){
					/*(Centroid is the average of all X / Y pixels)*/
					rigidbody_centroid.X /= num;
					rigidbody_centroid.Y /= num;
				}else{
					/*(Not data found)*/
					rigidbody_centroid.X = 0;
					rigidbody_centroid.Y = 0;
				}
			
			
			
			
				/*(Calculation)*/
				let _temp = [];
				/*(Lxx)*/
				_temp[0]=MomentOfInertia(pixels_density.Y, ~~rigidbody_centroid.Y);
				/*(Lyy)*/
				_temp[1]=MomentOfInertia(pixels_density.X, ~~rigidbody_centroid.X);
				/*(Profile height)*/
				_temp[2]=params.profile_width  * (dim_bound.bottom - dim_bound.top) / (dim_bound.right - dim_bound.left);
				/*(Mean radius)*/
				_temp[3]=(params.pipe_diameter / 2) + (_temp[2]-rigidbody_centroid.Y);
				
				
				/*(Flip Y center of mass)*/
				rigidbody_centroid.Y = 	_temp[2] - rigidbody_centroid.Y;
				
				/*(callback function, this will triger `onupdate` function and return the output object)*/
				settings.onupdate.call(this, {
					'source':{
						'centroid':source_centroid,
						'area':source_area,
						'boundary':dim_bound,
					},
					'extracted':{
						'centroid':rigidbody_centroid,
						'area':num,
						'height':_temp[2],
						'moi':{
							'lxx':_temp[0],
							'lyy':_temp[1]
						},
						/*( SR= (Lxx / 10) * (Young modulus short) / (Pipe radius / 100) ^ 3 )*/
						'sr': ((_temp[0] / 100) * params.data_youngmod_short) / ((params.pipe_diameter / 2 / 100) ** 3),
						/*( SN= SR / 8)*/
						'sn': ((_temp[0] / 100) * params.data_youngmod_short) / ((params.pipe_diameter / 2 / 100) ** 3) / 8
					},
					'pipe':{
						'inner_diameter': params.pipe_diameter,
						'outer_diameter': params.pipe_diameter + (_temp[2] * 2),
						'mean_radius': _temp[3],
						
						
						/*(Material density in g/cm2 convert to g/mm2)*/
						'mass': 
							/*pipe mean radius circumference*/((2 * _temp[3] * Math.PI) / params.profile_width) 
							* /*1mm profile mass*/(num * params.data_material_density / 1000) 
							* /*1m length*/(10 ** 3) 
							 /*( (10 ** 6) //* (10 **3))*/
					}
				});
				
				

				
				/*(Draw reference lines )*/
				/*( Centroid XY)*/
				/*( Boundary Only Y)*/
				if(source_centroid.X > 0 && source_centroid.Y >0){
					cp_cxt.translate(0.5, 0.5);
					
					/*(Use `scl_ratio` from scaling source image down to plot axis, this will help )*/
					/*(not using an intermediate canvas )*/
					cp_cxt.beginPath();
					cp_cxt.lineWidth = 1;
					cp_cxt.strokeStyle = settings.centroid_axis_color;
					cp_cxt.moveTo(0, ~~(source_centroid.Y * scl_ratio.Y));
					cp_cxt.lineTo(cp.width, ~~(source_centroid.Y * scl_ratio.Y));
					cp_cxt.moveTo(~~(source_centroid.X * scl_ratio.X), 0);
					cp_cxt.lineTo(~~(source_centroid.X * scl_ratio.X), cp.height);
					cp_cxt.stroke();
					
					cp_cxt.beginPath();
					cp_cxt.lineWidth = 1;
					cp_cxt.strokeStyle = settings.boundary_axis_color;
					cp_cxt.moveTo(~~(dim_bound.left * scl_ratio.X), 0);
					cp_cxt.lineTo(~~(dim_bound.left * scl_ratio.X), cp.height);
					cp_cxt.moveTo(~~(dim_bound.right * scl_ratio.X), 0);
					cp_cxt.lineTo(~~(dim_bound.right * scl_ratio.X), cp.height);
					cp_cxt.stroke();
					
					cp_cxt.beginPath();
					cp_cxt.lineWidth = 1;
					cp_cxt.strokeStyle = settings.boundary_axis_color;
					cp_cxt.moveTo(0, ~~(dim_bound.top * scl_ratio.Y));
					cp_cxt.lineTo(cp.width, ~~(dim_bound.top * scl_ratio.Y));
					cp_cxt.moveTo(0, ~~(dim_bound.bottom * scl_ratio.Y));
					cp_cxt.lineTo(cp.width, ~~(dim_bound.bottom * scl_ratio.Y));
					cp_cxt.stroke();
					cp_cxt.translate(-0.5, -0.5);
					
					/*(XY axis)*/
					DrawCords(cp_cxt, cp.height);
				}else{
					/*(XY axis)*/
					DrawCords(cp_cxt, cp.height);
				}		
				
			}
			
			};;;;

		gdImage.addEventListener("load", imageReceived, true);
		
		
		/*(On file recieved)*/
		fileReader.onload = function(event) {
			gdImage.src = event.target.result;
			};
		
		/*(Change encoded 64 image to Unit8)*/
		function b64ToUnit8Array(b64Image){
			var img = atob(b64Image.split(',')[1]);
			var img_buffer = [];
			var i = 0;
			while(i < img.length){
				img_buffer.push(img.charCodeAt(i));
				i++;
			}
			return new Uint8Array(img_buffer);
			};
		
		/*(Global properties setter)*/
		function set(value, property, options){
			value = parseFloat(value);
			if(_this.isNumber(value) && value > options.min && value <= options.max){
				params[property] = value;
			}else{
				throw new Error("Invalid input");
			}
			
			
			};
		
		/*(Object Class reference)*/
		var out = {
			/*(Properties Setter)*/
			'param':{
				set YoungModulus_short(value){set(value, 'data_youngmod_short', {min:0,max:2000});},
				set ProfileWidth(value){set(value, 'profile_width', {min:0,max:2000});},
				set ProfileThickness(value){set(value, 'profile_thickness', {min:0,max:100});},
				set PipeDiameter(value){set(value, 'pipe_diameter', {min:0,max:10000});},
				set MaterialDensity(value){set(value, 'data_material_density', {min:0,max:100});}
			},
			/*(Load image from input file (from HTML file browser) @file file - @void)*/
			"ReceiveFile": function(file){
				fileReader.readAsDataURL(file);
			},
			/*(Set color threshold @value int (0-255) - @void)*/
			"ColorThreshold":function(value){
				settings.color_threshold = ~~value;
			},
			/*(Invert colors @invert_bool bool - @void)*/
			"ColorInvert":function(invert_bool){
				settings.color_invert=!!invert_bool;
			},
			/*(Load image from URL @image_url string - @void)*/
			"LoadURL":function (image_url) {
				gdImage.src = image_url;
			},
			/*(Clear canvas)*/
			"Clear":function(){
				cp_cxt.clearRect(0, 0, cp.width, cp.height);
				ready_status=false;
			},
			/*(Set output canvas)*/
			"PlotCanvas":function(dom_element){
				cp = dom_element;
				cp_cxt = cp.getContext('2d');
			},
			/*(Save image on server)*/
			"SaveImage":function(){
				if(!ready_status){return false;}
				var u8Image = b64ToUnit8Array(cp.toDataURL('image/png'));
				var formData = new FormData();
				var xhr = new XMLHttpRequest();
				
				formData.append("imagefile", new Blob([u8Image], {type:"image/png"}));
				formData.append("method", "saveimage");
				
				xhr.onreadystatechange = function() {
					if (xhr.readyState == XMLHttpRequest.DONE) {
						alert(xhr.responseText);
					}
				}
				xhr.open("POST", "<?php echo $_SERVER['HTTP_SYSTEM_ROOT'].$pageinfo['directory'];?>", true);
				xhr.setRequestHeader("HTTP_X_REQUESTED_WITH", "XMLHttpRequest");
				xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
				
				/*(xhr.setRequestHeader("Content-Type", "multipart/form-data");)*/
				
				xhr.send(formData);
			},
			/*(Proccess input image)*/
			"Process":function () {
				imageProcess();
			},
			
			};;;;;;
		
		/*(Return output object for public use)*/
		return out;
	}
})(candas);	