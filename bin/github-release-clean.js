require('dotenv').config();

let githubRemoveAllReleases = require('github-remove-all-releases');

let AUTH = {
    type: 'oauth',
    token: process.env.GITHUB_TOKEN
};

// this is where the magic happens, we filter on tag.draft, if it's true, it will get deleted
let a_filter = function (tag) {
  let isDraft = Boolean(tag.draft || tag.prerelease);
  console.log( ` >>> ${tag.tag_name}: ${isDraft?"will be deleted...":"is kept."}` );
  return isDraft;
};

let a_callback = function (result) {
  console.log (result ? result : "Done.");
};

githubRemoveAllReleases(AUTH, process.env.GITHUB_OWNER, process.env.GITHUB_REPO, a_callback, a_filter);