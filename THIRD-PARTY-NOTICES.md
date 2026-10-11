# Third-party notices

Áron bundles the following third-party components inside the Windows
executable. They are redistributed unmodified under their own licences.

```
Third-party components redistributed in this directory
======================================================

The files in app/vad/ are NOT part of Áron. They are vendored, unmodified
copies of third-party voice-activity-detection libraries, self-hosted so that
in-browser VAD runs fully offline with no CDN dependency (see app/index.html,
where they are loaded). Each keeps the licence it ships under; all three are
MIT. Their copyright notices are reproduced below, as MIT requires.

Áron's own licence (also MIT) is in LICENSE at the repository root and does
not apply to these files.


1. ONNX Runtime Web  (onnxruntime-web)
   Copyright (c) Microsoft Corporation. All rights reserved.
   Licensed under the MIT License.
   https://github.com/microsoft/onnxruntime

   Files:
     ort.wasm.min.js
     ort-wasm-simd-threaded.mjs
     ort-wasm-simd-threaded.wasm
     ort-wasm-simd-threaded.jsep.mjs
     ort-wasm-simd-threaded.jsep.wasm


2. @ricky0123/vad-web
   Copyright (c) ricky0123 and contributors.
   Licensed under the MIT License.
   https://github.com/ricky0123/vad

   Files:
     bundle.min.js
     vad.worklet.bundle.min.js


3. Silero VAD  (model weights)
   Copyright (c) 2020-present Silero Team.
   Licensed under the MIT License.
   https://github.com/snakers4/silero-vad

   Files:
     silero_vad_v5.onnx


Full MIT licence text
---------------------

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

```
Third-party components redistributed in this directory
======================================================

The files in app/models/ are NOT part of Áron. They are vendored, unmodified
copies of third-party model weights, self-hosted so that the music detector
runs fully offline with no CDN dependency (see app/index.html, where they are
loaded, and app/server.py, which serves them at /models/).

Unlike app/vad/ -- whose contents are all MIT -- these are Apache-2.0, which
carries its own attribution requirements. Áron's own licence (MIT) is in
LICENSE at the repository root and does not apply to these files.


1. YAMNet  (model weights, ONNX conversion)
   Copyright 2019 The TensorFlow Authors. All Rights Reserved.
   Licensed under the Apache License, Version 2.0.
   https://github.com/tensorflow/models/tree/master/research/audioset/yamnet

   ONNX conversion obtained from:
     https://huggingface.co/andrelgomes/yamnet-onnx
     sha256 1510041dce24a2e9e84ec546807ac408ae496da6d1ed41bc3ccba649623f8e19

   Files:
     yamnet.onnx

2. YAMNet AudioSet class map
   Copyright 2019 The TensorFlow Authors. All Rights Reserved.
   Licensed under the Apache License, Version 2.0.
   https://github.com/tensorflow/models/blob/master/research/audioset/yamnet/yamnet_class_map.csv

   Files:
     yamnet_class_map.csv

   Retained so the class indices the detector relies on can be re-checked
   against their names rather than trusted from memory. (The Hugging Face
   model card lists "Singing=27"; the official map says 27 is "Chant" and
   Singing is 24. The detector follows this file.)


Apache License 2.0 -- required notices
--------------------------------------

Licensed under the Apache License, Version 2.0 (the "License"); you may not
use these files except in compliance with the License. You may obtain a copy
of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
License for the specific language governing permissions and limitations under
the License.
```
