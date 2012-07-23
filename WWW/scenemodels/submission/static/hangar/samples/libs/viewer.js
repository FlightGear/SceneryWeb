/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
References:
- "The AC3D file format" by Andy Colebourne
  http://www.inivis.com/ac3d/man/ac3dfileformat.html
*/

var AC = AC || {};
var HG = HG || {};

AC.SurfaceType = {
  POLYGON: 0,
  LINE_LOOP: 1,
  LINE_STRIP: 2
};

AC.SurfaceFlag = {
  SHADED: 0x10,
  TWO_SIDED: 0x20
};

AC.File = function(data, texture_path){
  var stream = new AC.Stream(data);
  
  this.materials = [];
  this.objects = [];
  this.text_path = texture_path;

  this.parse(stream);
};

AC.File.prototype.parse = function(stream){
  var transform = mat4.identity();

  stream.readToken(); //AC3Db
  
  while( stream.pending() ){
    switch( stream.readToken() ){
      case "MATERIAL":
        this.materials.push( new AC.Material(stream) );
        break;
      case "OBJECT":
        this.objects.push( new AC.Object(stream, transform, this.text_path) );
        break;
    }
  }
};

AC.Material = function(stream){
  this.name = stream.readString();
  stream.readToken(); //rgb
  this.diffuse = stream.readVector(3);
  stream.readToken(); //amb
  this.ambient = stream.readVector(3);
  stream.readToken(); //emis
  this.emissive = stream.readVector(3);
  stream.readToken(); //spec
  this.specular = stream.readVector(3);
  stream.readToken(); //shi
  this.shininess = stream.readFloat();
  stream.readToken(); //trans
  this.transparency = stream.readFloat();
};

AC.Object = function(stream, parentTransform, texture_path){
  var kids;
  
  this.defaultValues();

  this.type = stream.readString();
  
  while(undefined === kids){
    switch( stream.readToken() ){
      case "name":
        this.name = stream.readString();
        break;
      case "data":
        this.data = stream.readBlob( stream.readInteger() );
        break;
      case "texture":
        this.texture = texture_path+stream.readString();
        break;
      case "texrep":
        this.textureRepeat = stream.readVector(2);
        break;
      case "texoff":
        this.textureOffset = stream.readVector(2);
        break;
      case "rot":
        this.rotation = stream.readVector(9);
        break;
      case "loc":
        this.translation = stream.readVector(3);
        break;
      case "crease":
        this.crease = stream.readFloat();
        break;
      case "url":
        this.url = stream.readString();
        break;
      case "numvert":
        this.vertices = this.parseVertices(stream, parentTransform);
        break;
      case "numsurf":
        this.surfaces = this.parseSurfaces(stream);
        break;
      case "kids":
        kids = stream.readInteger();
        if (0 !== kids){
          this.children = this.parseKids(stream, kids, parentTransform, texture_path);
        }
        break;
    }
  }
  
  if (this.surfaces){
    this.smoothNormals( this.sharedVertices() );
  }
};

AC.Object.prototype.defaultValues = function(){
  this.textureOffset = [0, 0];
  this.textureRepeat = [1, 1];
  this.rotation = [1, 0, 0, 0, 1, 0, 0, 0, 1];
  this.translation = [0, 0, 0];
  this.crease = 61.0;
};

AC.Object.prototype.parseVertices = function(stream, parentTransform){
  var vertices = [], vertice = vec3.create(), transform = mat4.identity(),
      numvert = stream.readInteger(),
      i = 0;
  
  this.compose(transform, this.rotation, this.translation);
  mat4.multiply(transform, parentTransform);

  for (; i < numvert; ++ i){
    vertice[0] = stream.readFloat();
    vertice[1] = stream.readFloat();
    vertice[2] = stream.readFloat();
    
    mat4.multiplyVec3(transform, vertice);
    
    vertices.push(vertice[0], vertice[1], vertice[2]);
  }
  
  return vertices;
};

AC.Object.prototype.parseSurfaces = function(stream){
  var surfaces = [], numsurf = stream.readInteger(), i = 0, surface;
  
  for (; i < numsurf; ++ i){
    surface = new AC.Surface(stream, this);
    if (!surface.degenerated){
      surfaces.push(surface);
    }
  }
  
  return surfaces;
};

AC.Object.prototype.parseKids = function(stream, kids, parentTransform, texture_path){
  var children = [], transform = mat4.identity(), i = 0;
  
  this.compose(transform, this.rotation, this.translation);
  mat4.multiply(transform, parentTransform);
  
  for (; i < kids; ++ i){
    stream.readToken(); //OBJECT
    children.push( new AC.Object(stream, transform, texture_path) );
  }
  
  return children;
};

AC.Object.prototype.sharedVertices = function(){
  var surfaces = this.surfaces, numsurf = surfaces.length,
      shared = [], i = 0, j,
      indices, refs, index, surface, adjacents;

  for (; i < numsurf; ++ i){
    surface = surfaces[i];
    
    indices = surface.indices;
    refs = indices.length;
    
    for (j = 0; j < refs; ++ j){
      index = indices[j];
      
      adjacents = shared[index];
      if (!adjacents){
        shared[index] = [surface];
      }else{
        if (adjacents.indexOf(surface) === -1){
          adjacents.push(surface);
        }
      }
    }
  }
  
  return shared;
};  

AC.Object.prototype.smoothNormals = function(shared){
  var surfaces = this.surfaces, numsurf = surfaces.length,
      angle = Math.cos(this.crease * Math.PI / 180),
      i = 0, j, k, len, indices, refs, index, surface,
      adjacents, adjacent, normals, ns, na, nx, ny, nz, mod;

  for (; i < numsurf; ++ i){
    surface = surfaces[i];

    surface.normals = normals = [];
    ns = surface.normal;
    
    indices = surface.indices;
    refs = indices.length;
    
    for (j = 0; j < refs; ++ j){
      index = indices[j];
      
      nx = ns[0];
      ny = ns[1];
      nz = ns[2];
      
      adjacents = shared[index];
      len = adjacents.length;
      
      for (k = 0; k < len; ++ k){
        adjacent = adjacents[k];
       
        if (surface !== adjacent){
          na = adjacent.normal;
          
          if (ns[0] * na[0] + ns[1] * na[1] + ns[2] * na[2] > angle * ns[3] * na[3]){
            nx += na[0];
            ny += na[1];
            nz += na[2];
          }
        }
      }
      
      normals.push(nx, ny, nz);
    }
  }
};

AC.Object.prototype.compose = function(transform, r, t){
  transform[0]  = r[0]; transform[1]  = r[1]; transform[2]  = r[2];
  transform[4]  = r[3]; transform[5]  = r[4]; transform[6]  = r[5];
  transform[8]  = r[6]; transform[9]  = r[7]; transform[10] = r[8];
  transform[12] = t[0]; transform[13] = t[1]; transform[14] = t[2];
};

AC.Surface = function(stream, object){
  var refs;

  while(undefined === refs){
    switch( stream.readToken() ){
      case "SURF":
        this.parseFlags(stream);
        break;
      case "mat":
        this.materialId = stream.readInteger();
        break;
      case "refs":
        refs = stream.readInteger();
        this.parseRefs(stream, object, refs);
        break;
    }
  }
  
  this.normal = [0.0, 0.0, 0.0, 1.0];
  
  if (this.type === AC.SurfaceType.POLYGON){
    if ( !this.teselate(object, refs) ){
      this.degenerated = true;
    }
  }
};

AC.Surface.prototype.parseFlags = function(stream){
  var flags = stream.readInteger();
  
  this.type = flags & 0x0f;
  this.isShaded = (flags & AC.SurfaceFlag.SHADED) !== 0;
  this.isTwoSided = (flags & AC.SurfaceFlag.TWO_SIDED) !== 0;
};

AC.Surface.prototype.parseRefs = function(stream, object, refs){
  var offsetU = object.textureOffset[0],
      offsetV = object.textureOffset[1],
      repeatU = object.textureRepeat[0],
      repeatV = object.textureRepeat[1],
      indices = [], uvs = [], i = 0;

  for (; i < refs; ++ i){
    indices.push( stream.readInteger() );
    
    uvs.push(offsetU + stream.readFloat() * repeatU);
    uvs.push(offsetV + stream.readFloat() * repeatV);
  }

  this.indices = indices;
  this.uvs = uvs;
};

AC.Surface.prototype.teselate = function(object, refs){
  var coherence = false;

  if (refs >= 3){
  
    coherence = this.calculateNormal(object);
    if (coherence){
    
      if (refs > 3){
        coherence = this.triangulate(object);
      }
    }
  }
  
  return coherence;
};

AC.Surface.prototype.calculateNormal = function(object){
  var v = object.vertices,
      i = this.indices,
      i1 = i[0] * 3,
      i2 = i[1] * 3,
      i3 = i[2] * 3,
      v1x = v[i2]     - v[i1],
      v1y = v[i2 + 1] - v[i1 + 1],
      v1z = v[i2 + 2] - v[i1 + 2],
      v2x = v[i3]     - v[i1],
      v2y = v[i3 + 1] - v[i1 + 1],
      v2z = v[i3 + 2] - v[i1 + 2],
      nx = v1y * v2z - v2y * v1z,
      ny = v1z * v2x - v2z * v1x,
      nz = v1x * v2y - v2x * v1y,
      mod = Math.sqrt(nx * nx + ny * ny + nz * nz);

  this.normal = [nx, ny, nz, mod];
  
  return mod > 1e-10;
};

AC.Surface.prototype.triangulate = function(object){
  var vertices = object.vertices, indices = this.indices,
      n = this.normal, x = 0, y = 1,
      vs = [], len = indices.length, i = 0,
      index, orden, max;
  
  max = Math.max( Math.abs(n[0]), Math.abs(n[1]), Math.abs(n[2]) );
  
  if (max === Math.abs(n[0]) ){
    x = 1;
    y = 2;
  }else if (max === Math.abs(n[1]) ){
    x = 0;
    y = 2;
  }

  for (; i < len; ++ i){
    index = indices[i] * 3;
    vs.push( {x: vertices[index + x], y: vertices[index + y]} );
  }

  orden = AC.Triangulator.triangulate(vs);
  if (orden){
    this.sortRefs(orden);
  }
  
  return null !== orden;
};

AC.Surface.prototype.sortRefs = function(orden){
  var indices = this.indices, uvs = this.uvs, si = [], su = [],
      len = orden.length, i = 0, index;

  for (; i < len; ++ i){
    index = orden[i];

    si.push( indices[index] );
    su.push( uvs[index * 2], uvs[index * 2 + 1] );
  }

  this.indices = si;
  this.uvs = su;
};

AC.Stream = function(data){
  this.buffer = data;
  this.position = 0;
};

AC.Stream.prototype.pending = function(){
  return this.position !== this.buffer.length;
};

AC.Stream.prototype.space = function(){
  var c = this.buffer[this.position];
  
  return (' ' === c) || ('\r' === c) || ('\n' === c);
};

AC.Stream.prototype.quote = function(){
  return '\"' === this.buffer[this.position];
};

AC.Stream.prototype.readToken = function(){
  var start = this.position, token;

  for (; this.pending() && !this.space(); ++ this.position);
  token = this.buffer.substring(start, this.position); 
  for (; this.pending() && this.space(); ++ this.position);
  
  return token;
};

AC.Stream.prototype.readString = function(){
  var quoted = this.quote(),
      fn = quoted? this.quote: this.space,
      start = this.position, token;

  this.position += quoted;
  for (; this.pending() && !fn.call(this); ++ this.position);
  
  token = this.buffer.substring(start + quoted, this.position); 
  
  this.position += quoted;
  for (; this.pending() && this.space(); ++ this.position);

  return token;
};

AC.Stream.prototype.readBlob = function(len){
  var blob = this.buffer.substr(this.position, len);
  
  this.position += len;
  for (; this.pending() && this.space(); ++ this.position);
  
  return blob;
};

AC.Stream.prototype.readInteger = function(){
  return parseInt( this.readToken() );
};

AC.Stream.prototype.readFloat = function(){
  return parseFloat( this.readToken() );
};

AC.Stream.prototype.readVector = function(len){
  var vector = [], i = 0;
  
  for (; i < len; ++ i){
    vector.push( this.readFloat() );
  }
  
  return vector;
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Camera = function(setup){
  this.eye = vec3.create(setup.eye);
  this.poi = vec3.create(setup.poi);
  this.up = vec3.create(setup.up);
  this.fov = setup.fov;
  
  this.computeMatrix();
};

HG.Camera.prototype.computeMatrix = function(){
  this.transformer = mat4.lookAt(this.eye, this.poi, this.up);
};

HG.Camera.prototype.zoom = function(factor){
  vec3.subtract(this.eye, this.poi);
  
  vec3.scale(this.eye, factor);

  vec3.add(this.eye, this.poi);

  this.computeMatrix();
};

HG.Camera.prototype.rotate = function(angle, axis){
  var q = this.quaternion(angle, axis);
  
  vec3.subtract(this.eye, this.poi);
  
  quat4.multiplyVec3(q, this.eye);
  quat4.multiplyVec3(q, this.up);

  vec3.add(this.eye, this.poi);

  this.computeMatrix();
};

HG.Camera.prototype.localAxis = function(){
  var axis = [], dir = vec3.create();
  
  vec3.subtract(this.eye, this.poi, dir);
    
  axis[2] = vec3.normalize( vec3.create(dir) );
  axis[1] = vec3.normalize( vec3.create(this.up) );
  axis[0] = vec3.normalize( vec3.cross(this.up, dir, dir) );
  
  return axis;
};

HG.Camera.prototype.quaternion = function(angle, axis){
  var q = quat4.create(),
      h = angle / 2.0,
      s = Math.sin(h);

  q[0] = axis[0] * s;
  q[1] = axis[1] * s;
  q[2] = axis[2] * s;
  q[3] = Math.cos(h);

  return q;
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Loader = {};

HG.Loader.loadText = function(url, object, callback, params){
  var request = new XMLHttpRequest();
  request.open("GET", url, true);
  request.overrideMimeType("text/plain; charset=x-user-defined");
  request.onload = function(){
    object[callback](this.responseText, params);
  };
  request.send();
};

HG.Loader.loadBinary = function(url, object, callback, params){
  var request = new XMLHttpRequest();
  request.open("GET", url, true);
  request.responseType = "arraybuffer";
  request.onload = function(){
    object[callback](this.response, params);
  };
  request.send();
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Renderer = function(canvas){
  this.gl = canvas.getContext("experimental-webgl", {alpha:false} );
};

HG.Renderer.BackgroundColor = [0.5, 0.5, 0.65, 1.0];
HG.Renderer.LightPosition = [-50, 50, 50];
HG.Renderer.LightAmbient = [0.2, 0.2, 0.2];

HG.Renderer.prototype.setScene = function(path, scene, camera){
  this.scene = scene;
  this.camera = camera;
  
  this.reset();
  
  this.programs = this.programs || this.createPrograms();
  this.textures = this.createTextures(path, scene.textures);
  this.buffers = this.createBuffers(scene.groups);
};

HG.Renderer.prototype.reset = function(){
  var gl = this.gl, color = HG.Renderer.BackgroundColor;

  gl.viewport(0, 0, gl.canvas.width, gl.canvas.height);
  gl.clearColor(color[0], color[1], color[2], color[3]);

  gl.enable(gl.DEPTH_TEST);
  gl.depthFunc(gl.LEQUAL);
  gl.depthMask(true);
  
  gl.enable(gl.CULL_FACE);
  gl.frontFace(gl.CCW);
  gl.cullFace(gl.BACK);
  
  gl.disable(gl.BLEND);
  gl.blendEquation(gl.FUNC_ADD);
  gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);

  this.depthMask = true;
  this.culling = true;
  this.blending = false;
  this.program = undefined;
};

HG.Renderer.prototype.resize = function(width, height){
  var gl = this.gl;

  gl.canvas.width = width;
  gl.canvas.height = height;

  gl.viewport(0, 0, width, height);
};

HG.Renderer.prototype.setDepthMask = function(depthMask){
  var gl = this.gl;
  
  if (this.depthMask !== depthMask){
    gl.depthMask(depthMask);
  }
  this.depthMask = depthMask;
};

HG.Renderer.prototype.setCulling = function(culling){
  var gl = this.gl;

  if (this.culling !== culling){
    if (culling){
      gl.enable(gl.CULL_FACE);
    }else{
      gl.disable(gl.CULL_FACE);
      }
  }
  this.culling = culling;
};

HG.Renderer.prototype.setBlending = function(blending){
  var gl = this.gl;

  if (this.blending !== blending){
    if (blending){
      gl.enable(gl.BLEND);
    }else{
      gl.disable(gl.BLEND);
    }
  }
  this.blending = blending;
};

HG.Renderer.prototype.setProgram = function(program){
  var gl = this.gl;

  if (this.program !== program){
    program.use(gl);
  }
  this.program = program;
};

HG.Renderer.prototype.render = function(){
  var gl = this.gl;
  
  gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);

  this.draw(gl, this.scene, false);
  this.draw(gl, this.scene, true);
};

HG.Renderer.prototype.draw = function(gl, scene, transparent){
  var groups = scene.groups, buffers = this.buffers,
      numgroups = groups.length, i = 0,
      projector, normalizer, group, material, program;

  projector = mat4.perspective(this.camera.fov,
    gl.drawingBufferWidth / gl.drawingBufferHeight, 0.1, 1000.0);
  
  normalizer = mat4.toInverseMat3(this.camera.transformer);
  mat3.transpose(normalizer);

  this.setBlending(transparent);
  this.setDepthMask(!transparent);

  for (i = 0; i < numgroups; ++ i){
    group = groups[i];

    material = scene.materials[group.materialId];
    if (transparent !== (0.0 === material.transparency) ){
    
      this.setCulling(!group.isTwoSided);
      
      program = this.getProgram(group);
      this.setProgram(program);
      
      gl.bindBuffer(gl.ARRAY_BUFFER, buffers[i]);
      
      gl.uniformMatrix4fv(program.uniforms.uProjector, false, projector);
      gl.uniformMatrix4fv(program.uniforms.uTransformer, false, this.camera.transformer);

      gl.vertexAttribPointer(program.attributes.aPosition, 3, gl.FLOAT, false, 32, 0);

      if (group.type !== AC.SurfaceType.POLYGON){
        gl.uniform4fv(program.uniforms.uColor, scene.materials[group.materialId].diffuse.concat(1.0) );
      }

      if (group.type === AC.SurfaceType.POLYGON){
        gl.uniformMatrix3fv(program.uniforms.uNormalizer, false, normalizer);
        gl.uniform3fv(program.uniforms.uEmissive, material.emissive);
        gl.uniform3fv(program.uniforms.uAmbient, material.ambient);
        gl.uniform3fv(program.uniforms.uDiffuse, material.diffuse);
        gl.uniform3fv(program.uniforms.uSpecular, material.specular);
        gl.uniform1f(program.uniforms.uShininess, material.shininess);
        gl.uniform1f(program.uniforms.uTransparency, material.transparency);
        gl.uniform3fv(program.uniforms.uLightPosition, HG.Renderer.LightPosition);
        gl.uniform3fv(program.uniforms.uLightAmbient, HG.Renderer.LightAmbient);

        gl.vertexAttribPointer(program.attributes.aNormal, 3, gl.FLOAT, false, 32, 20);
      }

      if (undefined !== group.textureId){
        gl.activeTexture(gl.TEXTURE0);
        gl.bindTexture(gl.TEXTURE_2D, this.textures[group.textureId]);
        
        gl.uniform1i(program.uniforms.uSampler, 0);
        
        gl.vertexAttribPointer(program.attributes.aTexcoord, 2, gl.FLOAT, false, 32, 12);
      }

      gl.drawArrays( this.getDrawMode(group.type), 0, group.buffer.length/8);
  
      gl.bindTexture(gl.TEXTURE_2D, null);
      gl.bindBuffer(gl.ARRAY_BUFFER, null);
    }
  }
};

HG.Renderer.prototype.getProgram = function(group){
  var program;
  
  if (group.type === AC.SurfaceType.POLYGON){
    if (group.textureId === undefined){
      program = this.programs.phong;
    }else{
      program = this.programs.phongTexture;
    }
  }else{
    if (group.textureId === undefined){
      program = this.programs.color;
    }else{
      program = this.programs.texture;
    }
  }
  
  return program;
};

HG.Renderer.prototype.getDrawMode = function(type){
  var gl = this.gl, mode;
  
  switch(type){
    case AC.SurfaceType.POLYGON:
      mode = gl.TRIANGLES;
      break;
    case AC.SurfaceType.LINE_STRIP:
      mode = gl.LINE_STRIP;
      break;
    case AC.SurfaceType.LINE_LOOP:
      mode = gl.LINE_LOOP;
      break;
  }

  return mode;
};

HG.Renderer.prototype.createPrograms = function(){
  var gl = this.gl;

  return {
    color: new HG.Shader(gl, HG.Shader.Color),
    texture: new HG.Shader(gl, HG.Shader.Texture),
    phong: new HG.Shader(gl, HG.Shader.Phong),
    phongTexture: new HG.Shader(gl, HG.Shader.PhongTexture)
  };
};

HG.Renderer.prototype.createBuffers = function(groups){
  var gl = this.gl, buffers = [], numgroups = groups.length, i = 0,
      buffer;
  
  for (; i < numgroups; ++ i){
    buffer = gl.createBuffer();
    buffers.push(buffer);
    
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(groups[i].buffer), gl.STATIC_DRAW);
    gl.bindBuffer(gl.ARRAY_BUFFER, null);
  }
  
  return buffers;
};

HG.Renderer.prototype.createTextures = function(path, filenames){
  var gl = this.gl, textures = [], len = filenames.length, i = 0,
      filename, texture;
  
  for (; i < len; ++ i){
    filename = path + filenames[i];
    
    texture = gl.createTexture();
    textures.push(texture);
    
    switch( this.getExtension(filename) ){
      case "sgi":
      case "rgba":
      case "rgb":
      case "ra":
      case "bw":
        HG.Loader.loadBinary(filename, this, "onSgiTextureLoaded", {texture:texture});
        break;
      default:
        this.loadTexture(filename, texture);
        break;
    }
  }
  
  return textures;
};

HG.Renderer.prototype.getExtension = function(filename){
  var extension = "", position;

  position = filename.lastIndexOf(".");
  if (-1 !== position){
    extension = filename.substring(position + 1);
  }
    
  return extension.toLowerCase();
};

HG.Renderer.prototype.onSgiTextureLoaded = function(data, params){
  var gl = this.gl,
      file = new SGI.File(data),
      pot = this.isImagePowerOfTwo(file.img),
      wrapMode = pot? gl.REPEAT: gl.CLAMP_TO_EDGE,
      filterMode = pot? gl.LINEAR_MIPMAP_LINEAR: gl.LINEAR;

  gl.bindTexture(gl.TEXTURE_2D, params.texture);
  
  gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
  gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, filterMode);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, filterMode);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, wrapMode);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, wrapMode);
  
  gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA,
                file.img.width, file.img.height, 0, gl.RGBA,
                gl.UNSIGNED_BYTE, file.img.data);

  if (filterMode === gl.LINEAR_MIPMAP_LINEAR){
    gl.generateMipmap(gl.TEXTURE_2D);
  }

  gl.bindTexture(gl.TEXTURE_2D, null);
};

HG.Renderer.prototype.loadTexture = function(filename, texture){
  var gl = this.gl, that = this, image = new Image(),
      pot, wrapMode, filterMode;
  
  image.onload = function(){
    pot = that.isImagePowerOfTwo(image);
    wrapMode = pot? gl.REPEAT: gl.CLAMP_TO_EDGE;
    filterMode = pot? gl.LINEAR_MIPMAP_LINEAR: gl.LINEAR;
  
    gl.bindTexture(gl.TEXTURE_2D, texture);
    
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, filterMode);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, filterMode);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, wrapMode);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, wrapMode);
    
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, image);
    
    if (filterMode === gl.LINEAR_MIPMAP_LINEAR){
      gl.generateMipmap(gl.TEXTURE_2D);
    }

    gl.bindTexture(gl.TEXTURE_2D, null);
  };
  
  image.src = filename;
};

HG.Renderer.prototype.isImagePowerOfTwo = function(img){
  return this.isPowerOfTwo(img.width) && this.isPowerOfTwo(img.height);
};

HG.Renderer.prototype.isPowerOfTwo = function(n){
  return 0 === (n & (n - 1) );
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Scene = function(file){
  this.materials = file.materials;
  this.textures = [];
  this.groups = [];
  this.boundingBox = new HG.BoundingBox();
  
  this.build(file.objects);
};

HG.Scene.prototype.build = function(objects){
  this.buildGroups(objects);

  this.groups.sort(HG.RenderGroup.sort);
};

HG.Scene.prototype.buildGroups = function(objects){
  var len = objects.length, i = 0, object;

  for (; i < len; ++ i){
    object = objects[i];
    
    if ("light" !== object.type && object.surfaces){
      this.buildGroup(object);
    }
    
    if (object.children){
      this.buildGroups(object.children);
    }
  }
};

HG.Scene.prototype.buildGroup = function(object){
  var texture = object.texture,
      vertices = object.vertices,
      surfaces = object.surfaces,
      numsurf = surfaces.length,
      bb = this.boundingBox,
      i = 0, j, k, l, x, y, z,
      indices, uvs, normals, normal, isShaded, refs,
      group, surface, buffer, index, textureId;

  if (texture){
    textureId = this.getTextureId(texture);
  }
  
  for (; i < numsurf; ++ i){
    surface = surfaces[i];
    
    indices = surface.indices;
    uvs = surface.uvs;
    normals = surface.normals;
    normal = surface.normal;
    isShaded = surface.isShaded;

    group = this.getGroup(surface, textureId);
    buffer = group.buffer;

    refs = indices.length;
    for (j = k = l = 0; j < refs; ++ j, k += 2, l += 3){
      index = indices[j] * 3;
    
      x = vertices[index];
      y = vertices[index + 1];
      z = vertices[index + 2];
    
      buffer.push(x, y, z);
    
      buffer.push(uvs[k], uvs[k + 1]);
      
      if (isShaded){
        buffer.push(normals[l], normals[l + 1], normals[l + 2]);
      }else{
        buffer.push(normal[0], normal[1], normal[2]);
      }
      
      bb.xmin = Math.min(bb.xmin, x);
      bb.xmax = Math.max(bb.xmax, x);
      bb.ymin = Math.min(bb.ymin, y);
      bb.ymax = Math.max(bb.ymax, y);
      bb.zmin = Math.min(bb.zmin, z);
      bb.zmax = Math.max(bb.zmax, z);
    }
  }
};

HG.Scene.prototype.getGroup = function(surface, textureId){
  var group = this.findGroup(surface, textureId);
  
  if (!group){
    group = new HG.RenderGroup(surface, textureId);
    this.groups.push(group);
  }
  
  return group;
};

HG.Scene.prototype.findGroup = function(surface, textureId){
  var i = this.groups.length - 1, group;

  for (; i >= 0; -- i){
    group = this.groups[i];
    if (group.materialId === surface.materialId &&
        group.textureId === textureId &&
        group.isTwoSided === surface.isTwoSided &&
        group.type === AC.SurfaceType.POLYGON &&
        surface.type === AC.SurfaceType.POLYGON){
      return group;
    }
  }
  
  return undefined;
};

HG.Scene.prototype.getTextureId = function(filename){
  var textures = this.textures, len = textures.length, i = 0;

  for (; i < len; ++ i){
    if (textures[i] === filename){
      break;
    }
  }
  if (i === len){
    textures.push(filename);
  }
  
  return i;
};

HG.RenderGroup = function(surface, textureId){
  this.materialId = surface.materialId;
  this.textureId = textureId;
  this.isTwoSided = surface.isTwoSided;
  this.type = surface.type;
  
  this.buffer = [];
};

HG.RenderGroup.sort = function(a, b){
  var texa = a.textureId === undefined? -1: a.textureId,
      texb = b.textureId === undefined? -1: b.textureId,
      sorted = a.type - b.type;
      
  if (0 === sorted){
    sorted = texa - texb;
    if (0 === sorted){
      sorted = a.materialId - b.materialId;
      if (0 === sorted){
        sorted = a.isTwoSided - b.isTwoSided;
      }
    }
  }
  
  return sorted;
};

HG.BoundingBox = function(){
  this.xmin = Infinity;
  this.xmax = -Infinity;
  this.ymin = Infinity;
  this.ymax = -Infinity;
  this.zmin = Infinity;
  this.zmax = -Infinity;
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
References:
- "The SGI Image File Format" by Paul Haeberli
  ftp://ftp.sgi.com/graphics/SGIIMAGESPEC
*/

var SGI = SGI || {};

SGI.Storage = {
  VERBATIM: 0,
  RLE: 1
};

SGI.File = function(data){
  var stream = new SGI.Stream(data);

  this.header = new SGI.Header(stream);
  this.img = new SGI.Image(this.header);

  this.parse(stream);
};

SGI.File.prototype.parse = function(stream){
  switch(this.header.storage){
    case SGI.Storage.VERBATIM:
      this.verbatim(stream);
      break;
    case SGI.Storage.RLE:
      this.rle(stream);
      break;
  }
  this.adjustChannels();
};

SGI.File.prototype.verbatim = function(stream){
  var img = this.img.data,
      channels = this.header.zsize,
      height = this.header.ysize,
      width = this.header.xsize,
      span = width * 8,
      channel = 0, src = 512, 
      row, col, dst;

  for (; channel < channels; ++ channel){
    dst = this.startChannel(channel);
    
    for (row = 0; row < height; ++ row, dst -= span){
      for (col = 0; col < width; ++ col, dst += 4){
        img[dst] = stream.peekByte(src ++);
      }
    }
  }
};

SGI.File.prototype.rle = function(stream){
  var img = this.img.data,
      channels = this.header.zsize,
      height = this.header.ysize,
      span = this.header.xsize * 4,
      channel = 0, starts = 512,
      row, src, dst;

  for (; channel < channels; ++ channel){
    dst = this.startChannel(channel);
  
    for (row = 0; row < height; ++ row, dst -= span, starts += 4){
      src = stream.peekLong(starts);
      
      this.rleRow(stream, src, img, dst);
    }
  }
};

SGI.File.prototype.rleRow = function(stream, src, img, dst){
  var value = stream.peekByte(src ++),
      count = value & 0x7f,
      i;

  while(0 !== count){
    
    if (value & 0x80){
      for (i = 0; i < count; ++ i, dst += 4){
        img[dst] = stream.peekByte(src ++);
      }
    }else{
      value = stream.peekByte(src ++);
      for (i = 0; i < count; ++ i, dst += 4){
        img[dst] = value;
      }
    }
    
    value = stream.peekByte(src ++);
    count = value & 0x7f;
  }
};

SGI.File.prototype.adjustChannels = function(){
  var img = this.img.data,
      size = img.length,
      channels = this.header.zsize,
      dst = 0;

  if (4 !== channels){
    for (; dst < size; dst += 4){
      switch(channels){
        case 1:
          img[dst + 1] = img[dst + 2] = img[dst];
          img[dst + 3] = 255;
          break;
        case 2:
          img[dst + 1] = img[dst + 2] = img[dst];
          break;
        case 3:
          img[dst + 3] = 255;
          break;
      }
    }
  }
};

SGI.File.prototype.startChannel = function(channel){
  var address = ( (this.header.ysize - 1) * this.header.xsize * 4);

  if ( (2 === this.header.zsize) && (1 === channel) ){
    address += 2;
  }
  
  return address + channel;
};

SGI.Header = function(stream){
  this.storage = stream.peekByte(2);
  this.xsize = stream.peekShort(6);
  this.ysize = stream.peekShort(8);
  this.zsize = stream.peekShort(10);
};

SGI.Image = function(header){
  this.width = header.xsize;
  this.height = header.ysize;
  this.data = new Uint8Array(header.xsize * header.ysize * 4);
};

SGI.Stream = function(data){
  this.buffer = new Uint8Array(data);
};

SGI.Stream.prototype.peekByte = function(offset){
  return this.buffer[offset];
};

SGI.Stream.prototype.peekShort = function(offset){
  return (this.peekByte(offset) << 8) | this.peekByte(offset + 1);
};

SGI.Stream.prototype.peekLong = function(offset){
  return (this.peekByte(offset) << 24) | (this.peekByte(offset + 1) << 16) |
         (this.peekByte(offset + 2) << 8) | this.peekByte(offset + 3);
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Shader = function(gl, definition){
  this.program = this.createProgram(gl, definition);
  this.attributes = definition.attributes(gl, this.program);
  this.uniforms = definition.uniforms(gl, this.program);
};

HG.Shader.prototype.createProgram = function(gl, definition){
  var program = gl.createProgram(), shader, code;

  code = definition.defines + definition.vs;
  shader = this.createShader(gl, gl.VERTEX_SHADER, code);
  gl.attachShader(program, shader);

  code = definition.defines + definition.fs;
  shader = this.createShader(gl, gl.FRAGMENT_SHADER, code);
  gl.attachShader(program, shader);

  gl.linkProgram(program);

  return program;
};

HG.Shader.prototype.createShader = function(gl, type, code){
  var shader = gl.createShader(type);
  
  gl.shaderSource(shader, code);
  gl.compileShader(shader);

  return shader;
};

HG.Shader.prototype.use = function(gl){
  gl.useProgram(this.program);

  for (var attribute in this.attributes){
    gl.enableVertexAttribArray(this.attributes[attribute]);
  }
};

HG.Shader.Color = {};

HG.Shader.Color.vs = "\
attribute vec3 aPosition;\n\
\n\
#ifdef TEXTURE\n\
attribute vec2 aTexcoord;\n\
#endif\n\
\n\
uniform mat4 uProjector;\n\
uniform mat4 uTransformer;\n\
\n\
#ifdef TEXTURE\n\
varying vec2 vTexcoord;\n\
#endif\n\
\n\
void main(){\n\
#ifdef TEXTURE\n\
  vTexcoord = aTexcoord;\n\
#endif\n\
\n\
  gl_Position = uProjector * uTransformer * vec4(aPosition, 1.0);\n\
}";

HG.Shader.Color.fs = "\
#ifdef GL_ES\n\
  precision highp float;\n\
#endif\n\
\n\
#ifdef TEXTURE\n\
varying vec2 vTexcoord;\n\
#endif\n\
\n\
uniform vec4 uColor;\n\
\n\
#ifdef TEXTURE\n\
uniform sampler2D uSampler;\n\
#endif\n\
\n\
void main(){\n\
#ifdef TEXTURE\n\
  gl_FragColor = texture2D(uSampler, vTexcoord) * uColor;\n\
#else\n\
  gl_FragColor = uColor;\n\
#endif\n\
}";

HG.Shader.Color.defines = "";

HG.Shader.Color.attributes = function(gl, program){
  return{
    aPosition: gl.getAttribLocation(program, "aPosition")
  };
};

HG.Shader.Color.uniforms = function(gl, program){
  return{
    uProjector: gl.getUniformLocation(program, "uProjector"),
    uTransformer: gl.getUniformLocation(program, "uTransformer"),
    uColor: gl.getUniformLocation(program, "uColor")
  };
};

HG.Shader.Texture = {};

HG.Shader.Texture.fs = HG.Shader.Color.fs;

HG.Shader.Texture.vs = HG.Shader.Color.vs;

HG.Shader.Texture.defines = "#define TEXTURE\n";

HG.Shader.Texture.attributes = function(gl, program){
  return{
    aPosition: gl.getAttribLocation(program, "aPosition"),
    aTexcoord: gl.getAttribLocation(program, "aTexcoord")
  };
};

HG.Shader.Texture.uniforms = function(gl, program){
  return{
    uProjector: gl.getUniformLocation(program, "uProjector"),
    uTransformer: gl.getUniformLocation(program, "uTransformer"),
    uColor: gl.getUniformLocation(program, "uColor"),
    uSampler: gl.getUniformLocation(program, "uSampler")
  };
};

HG.Shader.Phong = {};

HG.Shader.Phong.vs = "\
attribute vec3 aPosition;\n\
attribute vec3 aNormal;\n\
\n\
#ifdef TEXTURE\n\
attribute vec2 aTexcoord;\n\
#endif\n\
\n\
uniform mat4 uProjector;\n\
uniform mat4 uTransformer;\n\
uniform mat3 uNormalizer;\n\
\n\
varying vec3 vPosition;\n\
varying vec3 vNormal;\n\
\n\
#ifdef TEXTURE\n\
varying vec2 vTexcoord;\n\
#endif\n\
\n\
void main(){\n\
  vPosition = (uTransformer * vec4(aPosition, 1.0) ).xyz;\n\
  vNormal = normalize(uNormalizer * aNormal);\n\
\n\
#ifdef TEXTURE\n\
  vTexcoord = aTexcoord;\n\
#endif\n\
\n\
  gl_Position = uProjector * uTransformer * vec4(aPosition, 1.0);\n\
}";

HG.Shader.Phong.fs = "\
#ifdef GL_ES\n\
  precision highp float;\n\
#endif\n\
\n\
varying vec3 vPosition;\n\
varying vec3 vNormal;\n\
\n\
#ifdef TEXTURE\n\
varying vec2 vTexcoord;\n\
#endif\n\
\n\
uniform vec3 uEmissive;\n\
uniform vec3 uAmbient;\n\
uniform vec3 uDiffuse;\n\
uniform vec3 uSpecular;\n\
uniform float uShininess;\n\
uniform float uTransparency;\n\
\n\
uniform vec3 uLightPosition;\n\
uniform vec3 uLightAmbient;\n\
\n\
#ifdef TEXTURE\n\
uniform sampler2D uSampler;\n\
#endif\n\
\n\
void main(){\n\
  vec3 L = normalize(uLightPosition - vPosition);\n\
  vec3 E = normalize(-vPosition);\n\
  vec3 R = normalize( -reflect(L, vNormal) );\n\
\n\
#ifdef TEXTURE\n\
  vec4 sample = texture2D(uSampler, vTexcoord);\n\
\n\
  vec3 color = sample.rgb * \n\
    (uEmissive + \n\
     uAmbient * uLightAmbient + \n\
     uDiffuse * max( dot(vNormal, L), 0.0) ) + \n\
    uSpecular * 0.3 * pow( max( dot(R, E), 0.0), uShininess);\n\
\n\
  gl_FragColor = vec4(color, sample.a * (1.0 - uTransparency) );\n\
#else\n\
  vec3 color = uEmissive + \n\
    uAmbient * uLightAmbient + \n\
    uDiffuse * max( dot(vNormal, L), 0.0) + \n\
    uSpecular * 0.3 * pow( max( dot(R, E), 0.0), uShininess);\n\
\n\
  gl_FragColor = vec4(color, 1.0 - uTransparency);\n\
#endif\n\
}";

HG.Shader.Phong.defines = "";

HG.Shader.Phong.attributes = function(gl, program){
  return{
    aPosition: gl.getAttribLocation(program, "aPosition"),
    aNormal: gl.getAttribLocation(program, "aNormal")
  };
};

HG.Shader.Phong.uniforms = function(gl, program){
  return{
    uProjector: gl.getUniformLocation(program, "uProjector"),
    uTransformer: gl.getUniformLocation(program, "uTransformer"),
    uNormalizer: gl.getUniformLocation(program, "uNormalizer"),
    uEmissive: gl.getUniformLocation(program, "uEmissive"),
    uAmbient: gl.getUniformLocation(program, "uAmbient"),
    uDiffuse: gl.getUniformLocation(program, "uDiffuse"),
    uSpecular: gl.getUniformLocation(program, "uSpecular"),
    uShininess: gl.getUniformLocation(program, "uShininess"),
    uTransparency: gl.getUniformLocation(program, "uTransparency"),
    uLightPosition: gl.getUniformLocation(program, "uLightPosition"),
    uLightAmbient: gl.getUniformLocation(program, "uLightAmbient")
  };
};

HG.Shader.PhongTexture = {};

HG.Shader.PhongTexture.fs = HG.Shader.Phong.fs;

HG.Shader.PhongTexture.vs = HG.Shader.Phong.vs;

HG.Shader.PhongTexture.defines = "#define TEXTURE\n";

HG.Shader.PhongTexture.attributes = function(gl, program){
  return{
    aPosition: gl.getAttribLocation(program, "aPosition"),
    aNormal: gl.getAttribLocation(program, "aNormal"),
    aTexcoord: gl.getAttribLocation(program, "aTexcoord")
  };
};

HG.Shader.PhongTexture.uniforms = function(gl, program){
  return{
    uProjector: gl.getUniformLocation(program, "uProjector"),
    uTransformer: gl.getUniformLocation(program, "uTransformer"),
    uNormalizer: gl.getUniformLocation(program, "uNormalizer"),
    uEmissive: gl.getUniformLocation(program, "uEmissive"),
    uAmbient: gl.getUniformLocation(program, "uAmbient"),
    uDiffuse: gl.getUniformLocation(program, "uDiffuse"),
    uSpecular: gl.getUniformLocation(program, "uSpecular"),
    uShininess: gl.getUniformLocation(program, "uShininess"),
    uTransparency: gl.getUniformLocation(program, "uTransparency"),
    uLightPosition: gl.getUniformLocation(program, "uLightPosition"),
    uLightAmbient: gl.getUniformLocation(program, "uLightAmbient"),
    uSampler: gl.getUniformLocation(program, "uSampler")
  };
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
References:
- "Rotating the Camera with the Mouse" by Daniel Lehenbauer
  http://viewport3d.com/trackball.htm
*/

HG.Trackball = function(canvas, camera){
  this.canvas = canvas;
  this.camera = camera;

  this.x = 0;
  this.y = 0;
  this.down = false;

  this.addListeners(canvas);
};

HG.Trackball.prototype.addListeners = function(canvas){
  var that = this,
      md = function(event){ that.onMouseDown(event); },
      mw = function(event){ that.onMouseWheel(event); },
      ms = function(event){ that.onMouseWheel(event); };

  canvas.addEventListener("mousedown", md, false);
  canvas.addEventListener("mousewheel", mw, false);
  canvas.addEventListener("DOMMouseScroll", ms, false);
};

HG.Trackball.prototype.onMouseDown = function(event){
  var that = this;
  
  this.down = true;
  this.x = event.clientX - this.canvas.offsetLeft;
  this.y = event.clientY - this.canvas.offsetTop;

  this.mu = function(event){ that.onMouseUp(event); };
  this.mm = function(event){ that.onMouseMove(event); };

  document.addEventListener("mouseup", this.mu, false);
  document.addEventListener("mousemove", this.mm, false);

  event.preventDefault();
};

HG.Trackball.prototype.onMouseUp = function(event){
  this.down = false;

  document.removeEventListener("mouseup", this.mu, false);
  document.removeEventListener("mousemove", this.mm, false);

  event.preventDefault();
};

HG.Trackball.prototype.onMouseMove = function(event){
  var x, y;

  if (this.down){
    x = event.clientX - this.canvas.offsetLeft;
    y = event.clientY - this.canvas.offsetTop;

    if (x !== this.x || y !== this.y){
      this.track(this.x, this.y, x, y);
      
      this.x = x;
      this.y = y;
    }
  }
};

HG.Trackball.prototype.onMouseWheel = function(event){
  var wheel = event.wheelDelta? event.wheelDelta / 120: -event.detail;
  
  this.camera.zoom( Math.max(0.05, 1 - wheel * 0.05) );
  
  event.preventDefault();
  
  return false;
};

HG.Trackball.prototype.track = function(x1, y1, x2, y2){
  var p1 = this.project(x1, y1),
      p2 = this.project(x2, y2),
      angle = Math.acos( vec3.dot(p1, p2) ),
      axis = vec3.create();

  if (angle){
    vec3.cross(p1, p2, axis);
    vec3.normalize(axis);
    
    this.camera.rotate(-angle, axis);
  }
};

HG.Trackball.prototype.project = function(x, y){
  var axis = this.camera.localAxis(),
      p = this.projectBall(x, y),
      q = vec3.create();
  
  vec3.scale(axis[0], p[0]);
  vec3.scale(axis[1], p[1]);
  vec3.scale(axis[2], p[2]);

  vec3.add(axis[0], vec3.add(axis[1], axis[2]), q);
  
  vec3.normalize(q);

  return q;
};

HG.Trackball.prototype.projectBall = function(x, y){
  var p = vec3.create();

  p[0] = ( x / (this.canvas.width * 0.5) ) - 1.0;
  p[1] = 1.0 - ( y / (this.canvas.height * 0.5) );
  p[2] = 1.0 - p[0] * p[0] - p[1] * p[1];
  p[2] = p[2] > 0.0? Math.sqrt(p[2]): 0.0;
  
  return p;
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
References:
- "Efficient Polygon Triangulation" by John W. Ratcliff
  http://www.flipcode.com/archives/Efficient_Polygon_Triangulation.shtml
*/

AC.Triangulator = function(){
};

AC.Triangulator.triangulate = function(vertices){
  var result = [], indices = [], len = vertices.length,
      v = len - 1, count = 2 * len, i = 0, j,
      u, w, ccw;

  ccw = AC.Triangulator.ccw(vertices) > 0.0;
  for (; i < len; ++ i){
    indices.push(ccw? i: len - i - 1);
  }

  while(len > 2){
    if (count -- <= 0){
      return null;
    }

    u = v;
    v = u + 1; if (v >= len){ v = 0; }
    w = v + 1; if (w >= len){ w = 0; }

    if ( AC.Triangulator.snip(vertices, u, v, w, len, indices) ){
      result.push(indices[u], indices[v], indices[w]);

      for (j = v + 1; j < len; ++ j){
        indices[j - 1] = indices[j];
      }

      len --;
      count = 2 * len;
    }
  }

  return ccw? result: result.reverse();
};

AC.Triangulator.ccw = function(vertices){
  var a = 0.0, len = vertices.length, i = len - 1, j = 0;

  for (; j < len; i = j ++) {
    a += vertices[i].x * vertices[j].y - vertices[j].x * vertices[i].y;
  }

  return a;
};

AC.Triangulator.snip = function(vertices, u, v, w, len, indices){
  var ax = vertices[ indices[u] ].x,
      ay = vertices[ indices[u] ].y,
      bx = vertices[ indices[v] ].x,
      by = vertices[ indices[v] ].y,
      cx = vertices[ indices[w] ].x,
      cy = vertices[ indices[w] ].y,
      i = 0, px, py, ca, cb, cc;

  if ( (bx - ax) * (cy - ay) - (by - ay) * (cx - ax) < 1e-10){
    return false;
  }

  for (; i < len; ++ i){
    if ( (i !== u) && (i !== v) && (i !== w) ){
      px = vertices[ indices[i] ].x;
      py = vertices[ indices[i] ].y;

      ca = (cx - bx) * (py - by) - (cy - by) * (px - bx);
      cb = (bx - ax) * (py - ay) - (by - ay) * (px - ax);
      cc = (ax - cx) * (py - cy) - (ay - cy) * (px - cx);

      if ( (ca >= 0.0) && (cb >= 0.0) && (cc >= 0.0) ){
        return false;
      }
    }
  }

  return true;
};
/*
Copyright (c) 2012 Juan Mellado

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

HG.Viewer = function(canvas){
  this.canvas = canvas;
  this.renderer = new HG.Renderer(canvas);
};

HG.Viewer.prototype.show = function(filename, setup, callback){
  var params = {filename: filename, setup: setup, callback:callback};

  HG.Loader.loadText(filename, this, "onModelLoaded", params);
};

HG.Viewer.prototype.onModelLoaded = function(data, params){
  var file = new AC.File(data, params.setup.texture_path),
      scene = new HG.Scene(file),
      camera = new HG.Camera( params.setup || this.fitToBoundingBox(scene) );

  this.renderer.setScene( this.getPath(params.filename), scene, camera);

  this.trackball = new HG.Trackball(this.canvas, camera);
  
  this.tick();
  
  params.callback();
};

HG.Viewer.prototype.tick = function(){
  var that = this;

  requestAnimationFrame( function(){ that.tick(); } );

  this.renderer.render();
};

HG.Viewer.prototype.onResize = function(width, height){
  this.renderer.resize(width, height);
};

HG.Viewer.prototype.fitToBoundingBox = function(scene){
  var setup = {}, bb = scene.boundingBox,
      dir = vec3.create(), distance;
  
  setup.eye = vec3.create();
  setup.poi = vec3.create();
  setup.up = [0.0, 1.0, 0.0];
  setup.fov = 45.0;
  
  setup.eye[0] = bb.xmin;
  setup.eye[1] = bb.ymax;
  setup.eye[2] = bb.zmax;
  
  setup.poi[0] = (bb.xmax + bb.xmin) * 0.5;
  setup.poi[1] = (bb.ymax + bb.ymin) * 0.5;
  setup.poi[2] = (bb.zmax + bb.zmin) * 0.5;

  vec3.subtract(setup.eye, setup.poi, dir);
  distance = vec3.length(dir) / ( Math.tan(setup.fov * (Math.PI / 180.0) * 0.5) );
  vec3.normalize(dir);
  vec3.scale(dir, distance);

  setup.eye = dir;
  
  return setup;
};

HG.Viewer.prototype.getPath = function(filename){
  var path = "", position;

  position = filename.lastIndexOf("/");
  if (-1 !== position){
    path = filename.substring(0, position + 1);
  }
    
  return path;
};
