(()=>{"use strict";var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var o in r)e.o(r,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:r[o]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.React,r=window.wp.components,o=window.wp.blocks,n=window.wp.serverSideRender;var a=e.n(n);const l=window.wp.blockEditor,i=JSON.parse('{"UU":"wporg/global-header"}');(0,o.registerBlockType)(i.UU,{edit:({attributes:e})=>(0,t.createElement)("div",{...(0,l.useBlockProps)()},(0,t.createElement)(r.Disabled,null,(0,t.createElement)(a(),{block:i.UU,attributes:e,skipBlockSupportAttributes:!0})))})})();