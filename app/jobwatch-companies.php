<?php
/**
 * Job-watch watchlist: companies whose ATS boards get polled directly.
 * Format: 'slug' => 'ats' where ats is greenhouse | lever | ashby | smartrecruiters.
 * Wrong or dead slugs cost nothing (they 404 and land in the errors list,
 * so prune or fix them when they show up there). Add freely.
 */
return [
    // Dev tools / infrastructure
    'gitlab' => 'greenhouse', 'vercel' => 'greenhouse', 'netlify' => 'lever',
    'hashicorp' => 'greenhouse', 'datadog' => 'greenhouse', 'mongodb' => 'greenhouse',
    'digitalocean' => 'greenhouse', 'cloudflare' => 'greenhouse', 'twilio' => 'greenhouse',
    'linear' => 'ashby', 'framer' => 'ashby', 'replit' => 'ashby', 'supabase' => 'ashby',
    'postman' => 'greenhouse', 'docker' => 'greenhouse', 'grafanalabs' => 'greenhouse',
    'anthropic' => 'greenhouse', 'openai' => 'ashby', 'perplexityai' => 'ashby',

    // Design / creative tools
    'figma' => 'greenhouse', 'canva' => 'lever', 'webflow' => 'greenhouse',
    'sketch' => 'ashby', 'invisionapp' => 'greenhouse', 'dribbble' => 'lever',

    // Fintech (the Bankrate/Fabrinomics lane)
    'stripe' => 'greenhouse', 'plaid' => 'lever', 'affirm' => 'greenhouse',
    'chime' => 'greenhouse', 'brex' => 'greenhouse', 'ramp' => 'ashby',
    'mercury' => 'greenhouse', 'coinbase' => 'greenhouse', 'kraken' => 'lever',
    'robinhood' => 'greenhouse', 'wealthsimple' => 'lever', 'nerdwallet' => 'greenhouse',
    'creditkarma' => 'greenhouse', 'sofi' => 'greenhouse', 'marqeta' => 'greenhouse',
    'billcom' => 'greenhouse', 'gusto' => 'greenhouse', 'deel' => 'ashby',
    'rippling' => 'greenhouse', 'carta' => 'greenhouse',

    // Education / learning (the SparkForce lane)
    'masterclass' => 'greenhouse', 'skillshare' => 'greenhouse', 'coursera' => 'greenhouse',
    'udemy' => 'greenhouse', 'duolingo' => 'greenhouse', 'outschool' => 'lever',
    'newsela' => 'greenhouse', 'degreed' => 'greenhouse', 'guildeducation' => 'greenhouse',
    'joinhandshake' => 'greenhouse', 'khanacademy' => 'greenhouse', 'brilliant' => 'lever',
    'codecademy' => 'greenhouse', 'quizlet' => 'greenhouse',

    // Consumer / product
    'discord' => 'greenhouse', 'reddit' => 'greenhouse', 'dropbox' => 'greenhouse',
    'vimeo' => 'greenhouse', 'patreon' => 'lever', 'substack' => 'lever',
    'squarespace' => 'greenhouse', 'wistia' => 'greenhouse', 'buffer' => 'lever',
    'hopin' => 'greenhouse', 'calm' => 'greenhouse', 'headspace' => 'greenhouse',
    'strava' => 'greenhouse', 'allbirds' => 'greenhouse', 'glossier' => 'greenhouse',
    'warbyparker' => 'greenhouse', 'oscar' => 'greenhouse', 'zocdoc' => 'greenhouse',
    'instacart' => 'greenhouse', 'doordash' => 'greenhouse', 'grubhub' => 'greenhouse',
    'etsy' => 'greenhouse', 'wayfair' => 'greenhouse', 'chewy' => 'greenhouse',

    // SaaS / B2B (complex-technical lane)
    'asana' => 'greenhouse', 'airtable' => 'greenhouse', 'notion' => 'greenhouse',
    'calendly' => 'greenhouse', 'zapier' => 'greenhouse', 'clickup' => 'greenhouse',
    'monday' => 'greenhouse', 'miro' => 'greenhouse', 'loom' => 'greenhouse',
    'intercom' => 'greenhouse', 'drift' => 'greenhouse', 'klaviyo' => 'greenhouse',
    'attentive' => 'greenhouse', 'braze' => 'greenhouse', 'amplitude' => 'greenhouse',
    'mixpanel' => 'greenhouse', 'segment' => 'greenhouse', 'gong' => 'greenhouse',
    'outreach' => 'greenhouse', 'salesloft' => 'greenhouse', 'zoominfo' => 'greenhouse',
    'lattice' => 'greenhouse', 'greenhouse' => 'greenhouse', 'lever' => 'lever',
    'abnormalsecurity' => 'greenhouse', 'crowdstrike' => 'greenhouse', 'snyk' => 'greenhouse',
    '1password' => 'lever', 'tailscale' => 'greenhouse', 'vanta' => 'ashby',

    // Healthcare / B2B (surfaced by the morning runs)
    'alma' => 'greenhouse', 'veeamsoftware' => 'greenhouse',
    'jackmortonworldwide' => 'greenhouse',

    // Media / entertainment
    'netflix' => 'lever', 'spotify' => 'lever', 'soundcloud' => 'greenhouse',
    'bandcamp' => 'greenhouse', 'splice' => 'greenhouse', 'axios' => 'greenhouse',
    'voxmedia' => 'greenhouse', 'theathletic' => 'greenhouse', 'nytimes' => 'greenhouse',
];
