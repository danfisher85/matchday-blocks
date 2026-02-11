#!/usr/bin/env node

/**
 * Post-process CSS to remove Tailwind's base layer (reset/normalize styles)
 * Keeps only the custom theme properties and component styles
 */

import { readFileSync, writeFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const cssFile = join(__dirname, '../assets/css/blocks.css');

try {
	let css = readFileSync(cssFile, 'utf8');

	// Remove the @layer base { ... } block
	css = css.replace(/@layer base \{[\s\S]*?\n\}/gm, '');

	// Remove the base layer from @layer declarations
	css = css.replace(/@layer theme, base, components, utilities;/g, '@layer theme, components, utilities;');

	// Write the modified CSS back
	writeFileSync(cssFile, css, 'utf8');

	console.log('✓ Removed base layer from blocks.css');
} catch (error) {
	console.error('Error processing CSS:', error.message);
	process.exit(1);
}
