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
    'typeform' => 'greenhouse', 'huckleberrylabs' => 'lever',
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

    // Healthcare / consumer health
    'ag1' => 'greenhouse',
    'alma' => 'greenhouse', 'veeamsoftware' => 'greenhouse',
    'jackmortonworldwide' => 'greenhouse',

    // Media / entertainment
    // (netflix, nytimes, theathletic dropped Aug 17: moved to their own ATSs)
    'spotify' => 'lever', 'soundcloud' => 'greenhouse',
    'bandcamp' => 'greenhouse', 'splice' => 'greenhouse', 'axios' => 'greenhouse',
    'voxmedia' => 'greenhouse',

    /* ---- Aug 17, 2026 expansion. Best-guess ATS per slug; the self-healing
            prober in jobwatch.php corrects wrong guesses and flags dead
            slugs as prune candidates, so guesses are cheap. ---- */

    // Boards seen live in Aug 2026 search hits (slugs from real URLs)
    'weedmaps77' => 'greenhouse', 'movementstrategy' => 'greenhouse',
    'lumimeds' => 'greenhouse', 'hovercraft' => 'greenhouse',
    'powerdigitalmarketing' => 'greenhouse', 'mutinyjobs' => 'greenhouse',
    'hook' => 'greenhouse', 'nascompany' => 'greenhouse', 'remotecom' => 'greenhouse',
    'paxlabs' => 'greenhouse', 'maximustribe' => 'ashby', 'knowbe4' => 'greenhouse',
    'ezcater' => 'greenhouse', 'yurtsai' => 'greenhouse',

    // AI-native (Billy's strongest hook)
    'scaleai' => 'greenhouse', 'runwayml' => 'greenhouse', 'jasper' => 'greenhouse',
    'writer' => 'greenhouse', 'glean' => 'greenhouse', 'cohere' => 'ashby',
    'elevenlabs' => 'ashby', 'synthesia' => 'ashby', 'harvey' => 'ashby',
    'sierra' => 'ashby', 'decagon' => 'ashby', 'cognition' => 'ashby',
    'modal' => 'ashby', 'baseten' => 'ashby', 'lambda' => 'greenhouse',
    'coreweave' => 'greenhouse', 'huggingface' => 'ashby', 'pinecone' => 'greenhouse',
    'togetherai' => 'greenhouse', 'mistral' => 'lever', 'characterai' => 'ashby',
    'heygen' => 'ashby', 'captions' => 'ashby', 'descript' => 'greenhouse',
    'wandb' => 'lever', 'statsig' => 'ashby', 'clay' => 'ashby', 'attio' => 'ashby',

    // Dev tools / data (complex-technical lane, round two)
    'sentry' => 'greenhouse', 'newrelic' => 'greenhouse', 'honeycomb' => 'greenhouse',
    'pagerduty' => 'greenhouse', 'launchdarkly' => 'greenhouse', 'elastic' => 'greenhouse',
    'confluent' => 'greenhouse', 'databricks' => 'greenhouse', 'snowflake' => 'greenhouse',
    'dbtlabs' => 'greenhouse', 'fivetran' => 'greenhouse', 'retool' => 'greenhouse',
    'planetscale' => 'greenhouse', 'neon' => 'ashby', 'render' => 'ashby',
    'railway' => 'ashby', 'cockroachlabs' => 'greenhouse', 'redis' => 'greenhouse',
    'algolia' => 'greenhouse', 'sanity' => 'ashby', 'hex' => 'ashby',
    'okta' => 'greenhouse', 'samsara' => 'greenhouse', 'fastly' => 'greenhouse',
    'workos' => 'greenhouse', 'gem' => 'greenhouse',

    // B2B SaaS / martech (round two)
    'hubspot' => 'greenhouse', 'sproutsocial' => 'greenhouse', 'hootsuite' => 'greenhouse',
    'zendesk' => 'greenhouse', 'front' => 'greenhouse', 'pendo' => 'greenhouse',
    'fullstory' => 'greenhouse', 'activecampaign' => 'greenhouse', '6sense' => 'greenhouse',
    'demandbase' => 'greenhouse', 'coda' => 'greenhouse', 'pitch' => 'greenhouse',
    'linktree' => 'greenhouse', 'box' => 'greenhouse', 'dashlane' => 'greenhouse',
    'pandadoc' => 'greenhouse', 'toasttab' => 'greenhouse', 'bigcommerce' => 'greenhouse',
    'yotpo' => 'greenhouse', 'recharge' => 'greenhouse', 'gorgias' => 'greenhouse',
    'cultureamp' => 'greenhouse', '15five' => 'greenhouse', 'hibob' => 'greenhouse',
    'justworks' => 'greenhouse', 'oysterhr' => 'greenhouse', 'checkr' => 'greenhouse',
    'grammarly' => 'greenhouse', 'superhuman' => 'greenhouse', 'beehiiv' => 'ashby',
    'convertkit' => 'greenhouse', 'circle' => 'ashby', 'maven' => 'ashby',

    // Fintech (round two)
    'betterment' => 'greenhouse', 'acorns' => 'greenhouse', 'stash' => 'greenhouse',
    'publiccom' => 'greenhouse', 'm1' => 'greenhouse', 'wealthfront' => 'greenhouse',
    'upstart' => 'greenhouse', 'dave' => 'greenhouse', 'varo' => 'greenhouse',
    'current' => 'greenhouse', 'moneylion' => 'greenhouse', 'greenlight' => 'greenhouse',
    'alloy' => 'greenhouse', 'unit' => 'ashby', 'lithic' => 'greenhouse',
    'moderntreasury' => 'greenhouse', 'highnote' => 'ashby', 'increase' => 'ashby',
    'adyen' => 'greenhouse', 'checkout' => 'greenhouse', 'airwallex' => 'lever',
    'remitly' => 'greenhouse', 'transferwise' => 'greenhouse', 'monzo' => 'greenhouse',
    'melio' => 'greenhouse', 'tipalti' => 'greenhouse', 'tripactions' => 'greenhouse',
    'expensify' => 'greenhouse', 'bluevine' => 'greenhouse', 'redventures' => 'greenhouse',
    'lendingtree' => 'greenhouse', 'cleo' => 'greenhouse', 'monarch' => 'ashby',
    'rocketmoney' => 'greenhouse', 'ellevest' => 'greenhouse', 'titan' => 'greenhouse',
    'gemini' => 'greenhouse', 'paxos' => 'greenhouse', 'anchorage' => 'greenhouse',
    'fireblocks' => 'greenhouse', 'chainalysis' => 'greenhouse', 'consensys' => 'greenhouse',
    'alchemy' => 'greenhouse', 'uniswaplabs' => 'greenhouse', 'opensea' => 'greenhouse',
    'ledger' => 'lever',

    // Education / learning / workforce (round two)
    'articulate' => 'greenhouse', '2u' => 'greenhouse', 'kajabi' => 'greenhouse',
    'remind' => 'greenhouse', 'clever' => 'greenhouse', 'classdojo' => 'greenhouse',
    'multiverse' => 'greenhouse', 'springboard' => 'greenhouse', 'reforge' => 'greenhouse',
    'chegg' => 'greenhouse', 'udacity' => 'greenhouse', 'pluralsight' => 'greenhouse',
    'thinkific' => 'greenhouse', 'teachable' => 'greenhouse', 'docebo' => 'greenhouse',
    'sanalabs' => 'ashby', 'kahoot' => 'greenhouse', 'quizizz' => 'greenhouse',
    'ziprecruiter' => 'greenhouse', 'glassdoor' => 'greenhouse',

    // Consumer health / wellness (round two)
    'himshers' => 'greenhouse', 'ro' => 'greenhouse', 'noom' => 'greenhouse',
    'lyrahealth' => 'greenhouse', 'springhealth' => 'greenhouse', 'headway' => 'greenhouse',
    'modernhealth' => 'greenhouse', 'whoop' => 'greenhouse', 'ouraring' => 'greenhouse',
    'eightsleep' => 'greenhouse', 'tonal' => 'greenhouse', 'zwift' => 'greenhouse',
    'levelshealth' => 'greenhouse', 'functionhealth' => 'ashby', 'devoted' => 'greenhouse',
    'carbonhealth' => 'greenhouse', 'talkspace' => 'greenhouse', 'thirtymadison' => 'greenhouse',

    // Consumer / commerce (round two)
    'airbnb' => 'greenhouse', 'lyft' => 'greenhouse', 'pinterest' => 'greenhouse',
    'nextdoor' => 'greenhouse', 'thumbtack' => 'greenhouse', 'faire' => 'greenhouse',
    'thredup' => 'greenhouse', 'renttherunway' => 'greenhouse', 'stitchfix' => 'greenhouse',
    'everlane' => 'greenhouse', 'brooklinen' => 'greenhouse', 'peloton' => 'greenhouse',
    'opendoor' => 'greenhouse', 'redfin' => 'greenhouse', 'compass' => 'greenhouse',
    'medium' => 'greenhouse', 'twitch' => 'greenhouse', 'roblox' => 'greenhouse',
    'epicgames' => 'greenhouse', 'riotgames' => 'greenhouse', 'unity' => 'greenhouse',
    'niantic' => 'greenhouse', 'scopely' => 'greenhouse', 'zynga' => 'greenhouse',
    'supercell' => 'greenhouse', 'bungie' => 'greenhouse', 'buzzfeed' => 'greenhouse',
    'vice' => 'greenhouse',

    // Brand / design agencies on ATSs (in-house is the target, but CD
    // seats at these are real and remote-friendly; still subject to the
    // experiential AVOID rule)
    'instrument' => 'greenhouse', 'huge' => 'greenhouse', 'rga' => 'greenhouse',
    'droga5' => 'greenhouse', '72andsunny' => 'greenhouse', 'codeandtheory' => 'greenhouse',
    'workco' => 'greenhouse', 'basicagency' => 'greenhouse', 'collins' => 'greenhouse',
    'wolffolins' => 'greenhouse', 'prophet' => 'greenhouse', 'siegelgale' => 'greenhouse',
];
