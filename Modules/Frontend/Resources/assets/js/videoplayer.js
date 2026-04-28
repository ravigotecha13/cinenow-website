import videojs from 'video.js'
import 'videojs-contrib-ads'
import 'video.js/dist/video-js.css'
import 'videojs-youtube'

const Button = videojs.getComponent('Button');
let currentEpisodeId = null;
let currentEntertainmentId = null;
let currentEntertainmentType = null;
let currentEpisodeName = null; // Variable to store current episode name

// Create a custom button class

document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

  const player = videojs('videoPlayer', {
    techOrder: ['vimeo', 'youtube', 'html5', 'hls', 'embed'],
    autoplay: false,
    controls: true,
    controlBar: {
      subsCapsButton: {
        textTrackSettings: false // Disable "captions settings"
      }
    }
  });

  function stopAllVideoPlayers() {
    // Stop the main player
    if (player) {
      player.pause();
      player.muted(true);
      player.currentTime(0);
    }

    // Stop any video elements that might be playing
    const allVideos = document.querySelectorAll('video');
    allVideos.forEach(video => {
      video.pause();
      video.muted = true;
      video.currentTime = 0;
    });
  }
  const videoEl = document.getElementById('videoPlayer');
  // if (!videoEl) return;
  // Read variables from data-* attributes
  var contentId = videoEl.getAttribute('data-contentid') || '';
  var contentType = videoEl.getAttribute('data-contenttype') || '';
  var currentCategoryId = videoEl.getAttribute('data-category-id') || '';
  var contentVideoType = videoEl.getAttribute('content-video-type') || '';

  const access = document.querySelector('#videoPlayer').getAttribute('data-movie-access');
  const continueWatch = document.querySelector('#videoPlayer').getAttribute('data-continue-watch') === 'true';
  const videotype = document.querySelector('#videoPlayer').getAttribute('data-contentType');
  const content_type = document.querySelector('#videoPlayer').getAttribute('data-content_type');

  if (contentType != 'livetv') {
    player.ready(async function () {
      if (access === 'pay-per-view' && videotype === 'movie' && contentId) {
        // Check if the movie is purchased
        try {
          const response = await fetch(`${baseUrl}/api/check-movie-purchase?movie_id=${contentId}`);
          const data = await response.json();
          if (data.is_purchased) {
            // User has rented the movie - Enable progress control
            const progressControl = player.controlBar.progressControl;
            if (progressControl) progressControl.show();

            const skipButton = new SkipTrainerButton(player, {
              baseUrl: baseUrl // Pass baseUrl to the button
            });
            player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);

            let lastSavedPercentage = 0;
            const showRentAgainButton = () => {
              if (!document.getElementById('rent-again-btn')) {
                const rentBtn = document.createElement('a');
                rentBtn.id = 'rent-again-btn';
                rentBtn.className = 'vjs-control vjs-button';
                rentBtn.innerHTML = 'Rent Again';
                rentBtn.href = `${baseUrl}/movie/details/${contentId}`;
                rentBtn.style.cssText = 'position: absolute; top: 20px; right: 20px; background: rgba(229, 9, 20, 0.9); color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; z-index: 999; cursor: pointer; font-size: 14px; pointer-events: auto;';
                player.el().appendChild(rentBtn);
              }
            };

            // Check initial percentage from backend
            if (data.watched_percentage && data.watched_percentage >= 25) {
              showRentAgainButton();
            }

            const saveWatchProgress = (percentage) => {
              const currentTime = player.currentTime();
              const duration = player.duration();
              const toHMS = (seconds) => new Date(seconds * 1000).toISOString().substr(11, 8);
              
              const watchNowButton = document.getElementById('watchNowButton');
              const seasonWatchBtn = document.getElementById('seasonWatchBtn');
              const videoEl = document.getElementById('videoPlayer');

              const profileId = watchNowButton?.getAttribute('data-profile-id') ||
                                seasonWatchBtn?.getAttribute('data-profile-id') ||
                                videoEl.getAttribute('data-profile-id');

              fetch(`${baseUrl}/api/save-continuewatch`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                  entertainment_id: contentId,
                  entertainment_type: 'movie', // Since this is check-movie-purchase
                  percentage: percentage,
                  watched_time: toHMS(currentTime),
                  total_watched_time: toHMS(duration),
                  profile_id: profileId
                })
              }).catch(err => console.error('Error saving progress:', err));

              // Also update watch_progress for PPV resume logic
              fetch(`${baseUrl}/update-watch-progress`, {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                      entertainment_id: contentId,
                      entertainment_type: 'movie',
                      watched_percentage: percentage,
                      last_time_seconds: Math.floor(currentTime)
                  })
              }).catch(err => console.error('Error saving PPV progress:', err));
            };

            // Save progress on page unload
            window.addEventListener('beforeunload', () => {
                const duration = player.duration();
                if (duration > 0) {
                    const percentage = (player.currentTime() / duration) * 100;
                    saveWatchProgress(percentage);
                }
            });

            // Show "Rent Again" button after 25% for rented movies (One-time watch limit)
            player.on('timeupdate', function () {
              const duration = player.duration();
              const currentTime = player.currentTime();
              if (duration > 0) {
                const percentage = (currentTime / duration) * 100;
                
                if (percentage >= 25) {
                  showRentAgainButton();
                }

                // Save progress every 5% change
                if (Math.abs(percentage - lastSavedPercentage) >= 5) {
                   saveWatchProgress(percentage);
                   lastSavedPercentage = percentage;
                }
              }
            });
          } else {
            // User has NOT rented the movie (Preview Mode)
            
            // Hide progress control to prevent skipping
            const progressControl = player.controlBar.progressControl;
            if (progressControl) progressControl.hide();

            // Enforce 25% preview limit
            player.on('timeupdate', function () {
              const duration = player.duration();
              const currentTime = player.currentTime();
              if (duration > 0) {
                const percentage = (currentTime / duration) * 100;
                if (percentage >= 25) {
                  player.pause();
                  if (player.isFullscreen()) {
                    player.exitFullscreen();
                  }
                  player.currentTime(duration * 0.25); // Lock at limit
                  
                  // Show Ticket/Rent Button Overlay
                  if (!document.getElementById('rent-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.id = 'rent-overlay';
                    Object.assign(overlay.style, {
                      position: 'absolute',
                      top: '0',
                      left: '0',
                      width: '100%',
                      height: '100%',
                      backgroundColor: 'rgba(0, 0, 0, 0.85)',
                      display: 'flex',
                      flexDirection: 'column',
                      justifyContent: 'center',
                      alignItems: 'center',
                      zIndex: '1000',
                      color: 'white'
                    });
                    overlay.innerHTML = `
                      <h2 style="margin-bottom: 20px;">Preview Ended</h2>
                      <p style="margin-bottom: 20px;">You've watched 25% of the movie. Get a ticket to continue.</p>
                      <a href="${baseUrl}/movie/details/${contentId}" class="vjs-control" style="padding: 10px 20px; background: #e50914; color: white; text-decoration: none; border-radius: 4px; font-size: 16px; pointer-events: auto; cursor: pointer;">
                        Get Ticket
                      </a>
                    `;
                    player.el().appendChild(overlay);
                  }
                }
              }
            });
          }
        } catch (error) {
          console.error('Error checking movie purchase:', error);
        }
      } else if (access != 'pay-per-view' && !continueWatch) {
        const skipButton = new SkipTrainerButton(player, {
          baseUrl: baseUrl // Pass baseUrl to the button
        });
        player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);
      }
      const nextButton = new NextEpisodeButton(player);
      player.controlBar.addChild(nextButton, {}, player.controlBar.children().length - 1);
    });
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  let isVideoLoaded = false
  let currentVideoUrl = ''
  let isWatchHistorySaved = false
  let lastContinueWatchSavedAt = 0

  function toHMS(seconds) {
    const s = Math.max(0, Math.floor(Number(seconds) || 0))
    return new Date(s * 1000).toISOString().substr(11, 8)
  }

  function getContinueWatchPayload() {
    try {
      if (!isAuthenticated) return null
      const currentTime = player.currentTime ? player.currentTime() : 0
      const duration = (player.duration ? player.duration() : 0) || 0
      if (!currentTime || currentTime < 3) return null

      const entertainmentId = currentEntertainmentId
        || watchNowButton?.getAttribute('data-entertainment-id')
        || seasonWatchBtn?.getAttribute('data-entertainment-id')
      const entertainmentType = currentEntertainmentType
        || watchNowButton?.getAttribute('data-entertainment-type')
        || seasonWatchBtn?.getAttribute('data-entertainment-type')
      const profileId = watchNowButton?.getAttribute('data-profile-id')
        || seasonWatchBtn?.getAttribute('data-profile-id')
        || videoEl.getAttribute('data-profile-id')
      const episodeId = currentEpisodeId
        || watchNowButton?.getAttribute('data-episode-id')
        || seasonWatchBtn?.getAttribute('data-episode-id')

      if (!entertainmentId || !entertainmentType) return null

      const percentage = duration > 0 ? (currentTime / duration) * 100 : 0

      return {
        entertainment_id: entertainmentId,
        entertainment_type: entertainmentType,
        total_watched_time: toHMS(duration),
        watched_time: toHMS(currentTime),
        episode_id: episodeId,
        profile_id: profileId,
        video_url: currentVideoUrl,
        percentage: percentage
      }
    } catch (e) {
      return null
    }
  }

  function saveContinueWatchProgress(useBeacon) {
    const payload = getContinueWatchPayload()
    if (!payload) return

    const url = `${baseUrl}/api/save-continuewatch`
    const body = JSON.stringify(payload)

    if (useBeacon && navigator.sendBeacon) {
      try {
        const blob = new Blob([body], { type: 'application/json' })
        navigator.sendBeacon(url, blob)
        lastContinueWatchSavedAt = Date.now()
        return
      } catch (_) {}
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: body,
      keepalive: true
    }).then(() => {
      lastContinueWatchSavedAt = Date.now()
    }).catch((error) => console.error('Error saving continue watching:', error))
  }

  player.on('timeupdate', function () {
    if (!isAuthenticated) return
    if (Date.now() - lastContinueWatchSavedAt < 15000) return
    saveContinueWatchProgress(false)
  })

  player.on('pause', function () {
    if (!isAuthenticated) return
    if (player.seeking && player.seeking()) return
    if (player.ended && player.ended()) return
    saveContinueWatchProgress(false)
  })

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      saveContinueWatchProgress(true)
    }
  })

  window.addEventListener('pagehide', function () {
    saveContinueWatchProgress(true)
  })

  async function CheckDeviceType() {
    try {
      const response = await fetch(`${baseUrl}/check-device-type`)
      const data = await response.json()
      return data.isDeviceSupported
    } catch (error) {
      return false
    }
  }

  async function CheckSubscription(planId) {
    try {
      const response = await fetch(`${baseUrl}/check-subscription/${planId}`)
      const data = await response.json()
      return data.isActive
    } catch (error) {
      return false
    }
  }

  // Reusable function to save entertainment views
  async function saveEntertainmentView(button) {
    try {
      const entertainmentId = button?.getAttribute('data-entertainment-id');
      const profileId = button?.getAttribute('data-profile-id');
      const typeOfContent = button?.getAttribute('data-entertainment-type');

      if (entertainmentId && ['movie', 'tvshow', 'video'].includes(typeOfContent)) {
        await fetch(`${baseUrl}/api/save-entertainment-views`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            entertainment_id: entertainmentId,
            profile_id: profileId,
          })
        });
      }
    } catch (error) {
      console.error('Error saving entertainment view:', error);
    }
  }

  async function checkAuthenticationAndDeviceSupport() {
    const isDeviceSupported = await CheckDeviceType()
    return isAuthenticated && isDeviceSupported
  }
//global function to set subtitle
  function setSubtitle(videoPlayer, subtitleInfo) {
    if (subtitleInfo && subtitleInfo.length > 0) {
      // Remove any existing subtitle tracks
      const existingTracks = videoPlayer.textTracks();
      for (let i = existingTracks.length - 1; i >= 0; i--) {
        videoPlayer.removeRemoteTextTrack(existingTracks[i]);
      }

      // Add new subtitle tracks
      subtitleInfo.forEach(subtitle => {
        videoPlayer.addRemoteTextTrack({
          kind: 'subtitles',
          src: subtitle.subtitle_file,
          srclang: subtitle.language_code,
          label: subtitle.language,
          default: subtitle.is_default === 1
        }, false);
      });

      setTimeout(() => {
        const tracks = player.textTracks();
        for (let i = 0; i < tracks.length; i++) {
          if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
            tracks[i].mode = 'showing';
          } else {
            tracks[i].mode = 'disabled';
          }
        }
      }, 0);
    }
  }

  async function loadVideoIfAuthenticated() {


    const accessType = document.querySelector('#videoPlayer').getAttribute('data-movie-access')

    const plan_id = document.querySelector('#videoPlayer').getAttribute('data-plan-id')

    // if (!isAuthenticated) {
    //   return // Exit if not authenticated
    // }

    let canPlay = true
    if (accessType === 'paid') {
      canPlay = await checkAuthenticationAndDeviceSupport()

    }

    if (plan_id && accessType === 'paid') {

      canPlay = await CheckSubscription(plan_id)
    }

    //when content is paid but play the trailer
    if(contentVideoType == 'trailer')
    {
      canPlay = true;
    }

    if (canPlay && !isVideoLoaded) {
      const encryptedData = document.querySelector('#videoPlayer').getAttribute('data-encrypted')
      const watchTime = document.querySelector('#videoPlayer').getAttribute('data-watch-time')

      // Set global variables for watch history tracking
      // Try to get from watch buttons first, then from video element
      // this is global variable set for the watch history tracking
      const videoEl = document.getElementById('videoPlayer');
      const watchNowButton = document.getElementById('watchNowButton');
      const seasonWatchBtn = document.getElementById('seasonWatchBtn');

      currentEntertainmentId = watchNowButton?.getAttribute('data-entertainment-id') ||
                               seasonWatchBtn?.getAttribute('data-entertainment-id') ||
                               videoEl.getAttribute('data-entertainment-id') ||
                               document.querySelector('meta[name="entertainment-id"]')?.getAttribute('content');
      currentEntertainmentType = watchNowButton?.getAttribute('data-entertainment-type') ||
                                seasonWatchBtn?.getAttribute('data-entertainment-type') ||
                                videoEl.getAttribute('data-entertainment-type') ||
                                document.querySelector('meta[name="entertainment-type"]')?.getAttribute('content');
      currentEpisodeId = watchNowButton?.getAttribute('data-episode-id') ||
                        seasonWatchBtn?.getAttribute('data-episode-id') ||
                        videoEl.getAttribute('data-episode-id') ||
                        document.querySelector('meta[name="episode-id"]')?.getAttribute('content');
      currentVideoUrl = encryptedData; // Use encrypted data as video URL for tracking

      // Hide the big play button if encryptedData is empty
      const bigPlayButton = player.el().querySelector('.vjs-big-play-button');
      if (!encryptedData && bigPlayButton) {
        bigPlayButton.style.display = 'none';
        // Optionally, you could show a message to the user here
        // e.g., player.overlay({ content: 'Video not available.' });
        return; // Stop here if no video data
      }

      let watchTimeInSeconds = 0;
      if (watchTime) {
        const parts = watchTime.split(':').map(Number);
        if (parts.length === 3) {
          watchTimeInSeconds = (parts[0] * 3600) + (parts[1] * 60) + parts[2];
        } else if (parts.length === 2) {
          watchTimeInSeconds = (parts[0] * 60) + parts[1];
        } else if (parts.length === 1) {
          watchTimeInSeconds = parts[0];
        }
      }
      if (Number.isFinite(watchTimeInSeconds) && watchTimeInSeconds > 0) {
        player.currentTime(watchTimeInSeconds);
      }
      const subtitleInfoforwatchlist = JSON.parse(
        watchNowButton?.getAttribute('data-subtitle-info') ||
        seasonWatchBtn?.getAttribute('data-subtitle-info') ||
        '[]'
      );
      // console.log('-----------------------------------------------------------------',continueWatch)
      if (encryptedData) {
        fetch(`${baseUrl}/video/stream/${encodeURIComponent(encryptedData)}`)
          .then((response) => response.json())
          .then((data) => {
            const qualityOptions = data.qualityOptions
            setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions)
            player.load();
            if (continueWatch == true) {
              setSubtitle(player, subtitleInfoforwatchlist);
            }
            player.one('loadedmetadata', async function () {
              player.muted(true) // Mute the player for autoplay
              try {
                if (Number.isFinite(watchTimeInSeconds) && watchTimeInSeconds > 0) {
                  player.currentTime(watchTimeInSeconds);
                }
                await player.play()
              } catch (error) {
                console.error('Error trying to autoplay:', error)
              }
            })
            isVideoLoaded = true
          })
          .catch((error) => console.error('Error fetching video:', error))
      }
    }
    else {
      $('#DeviceSupport').modal('show')
    }
  }

  loadVideoIfAuthenticated()
  const playButton = document.querySelector('.vjs-big-play-button')
  if (playButton) {
    playButton.addEventListener('click', async function (e) {
      if (!isAuthenticated) {
        e.preventDefault() // Prevent play
        window.location.href = loginUrl // Redirect to login
      } else {
        const canPlay = await checkAuthenticationAndDeviceSupport()
        if (!canPlay) {
          e.preventDefault() // Prevent play if conditions are not met
          $('#DeviceSupport').modal('show');
          player.pause && player.pause();
        }
      }
    })
  }

  const handleWatchButtonClick = async (button, isSeasonWatch = false) => {

    const accessType = button.getAttribute('data-movie-access')
    const qualityOptionsData = button.getAttribute('data-quality-options')
    const qualityOptions = Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url }))
    const videoUrl = button.getAttribute('data-video-url')
    currentVideoUrl = videoUrl
    contentVideoType = button.getAttribute('content-video-type');

    currentEpisodeId = button.getAttribute('data-episode-id');
    currentEntertainmentId = button.getAttribute('data-entertainment-id');
    currentEntertainmentType = button.getAttribute('data-entertainment-type');

    currentEpisodeName = button.getAttribute('data-episode-name') || '';
    // Update episode name display
    if (window.updateEpisodeNameDisplay) {
        window.updateEpisodeNameDisplay(currentEpisodeName);
    }

    // Get subtitle data from the button and parse it
    const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]')

    // Hide subtitle button if no subtitles are available
    const subtitleButton = player.controlBar.subsCapsButton
    if (subtitleButton) {
      if (subtitleInfo && subtitleInfo.length > 0) {
        subtitleButton.show()
      } else {
        subtitleButton.hide()
      }
    }

    // showCustomAdThenPlayMain();

    window.scrollTo({ top: 0, behavior: 'smooth' })

    const entertainmentId = button.getAttribute('data-entertainment-id')
    const entertainmentType = button.getAttribute('data-entertainment-type')
    const profileId = button.getAttribute('data-profile-id')

    let lastWatchedTime = 0
    try {
      if (accessType === 'pay-per-view') {
        const accessRes = await fetch(`${baseUrl}/ppv/check-access`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json'
          },
          credentials: 'same-origin',
          body: JSON.stringify({ entertainment_id: entertainmentId })
        })
        const access = await accessRes.json()
        if (accessRes.status === 401 || access.status === 'unauthenticated') {
          window.location.href = typeof loginUrl !== 'undefined' ? loginUrl : `${baseUrl}/login`
          return
        }
        const paymentFallback =
          button.getAttribute('data-payment-url') ||
          `${baseUrl}/payment-form/pay-per-view?id=${encodeURIComponent(entertainmentId)}&type=${encodeURIComponent(
            entertainmentType || 'movie'
          )}`
        if (access.status === 'purchase_required') {
          window.location.href = paymentFallback
          return
        }
        if (access.status === 'consumed_or_completed') {
          alert('Ticket already consumed. Please purchase again.')
          window.location.href = paymentFallback
          return
        }
        if (access.status === 'ok') {
          lastWatchedTime = Number(access.resume_time || 0)
        }
      } else {
        const resumeRes = await fetch(`${baseUrl}/continue-watch/resume`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            entertainment_id: entertainmentId,
            entertainment_type: entertainmentType,
            profile_id: profileId
          })
        })
        const data = await resumeRes.json()
        lastWatchedTime = data.resume_time || 0
      }
    } catch (error) {
      console.error('Error fetching resume time:', error)
    }

    if (accessType === 'paid') {
      const canPlay = await checkAuthenticationAndDeviceSupport()
      if (!canPlay) {
        player.pause()
        $('#DeviceSupport').modal('show') // Show device support modal if not supported
        return // Stop further execution
      }
    }
    if (accessType === 'free' || accessType === 'pay-per-view') {
      playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
    } else {
      handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
    }

    isWatchHistorySaved = false // Reset flag
  }

  const watchNowButton = document.getElementById('watchNowButton')
  const seasonWatchBtn = document.getElementById('seasonWatchBtn')

  const subtitles = JSON.parse(watchNowButton.getAttribute('data-subtitle-info'));

  const AUTO_RESUME_KEY = 'cinenow:auto-resume-after-nav';

  function markAutoResume() {
    try {
      const btn = watchNowButton || seasonWatchBtn;
      if (!btn) return;
      const entertainmentId = btn.getAttribute('data-entertainment-id');
      const entertainmentType = btn.getAttribute('data-entertainment-type');
      if (!entertainmentId || !entertainmentType) return;

      sessionStorage.setItem(AUTO_RESUME_KEY, JSON.stringify({
        id: entertainmentId,
        type: entertainmentType,
        ts: Date.now()
      }));
    } catch (_) {}
  }

  document.querySelectorAll('.dropdown-menu-language a[href]').forEach(function (link) {
    link.addEventListener('click', function () {
      if (player && typeof player.currentTime === 'function' && player.currentTime() > 3) {
        saveContinueWatchProgress(true);
        markAutoResume();
      }
    });
  });

  (function handleAutoResumeAfterNav() {
    try {
      const raw = sessionStorage.getItem(AUTO_RESUME_KEY);
      if (!raw) return;
      sessionStorage.removeItem(AUTO_RESUME_KEY);

      const data = JSON.parse(raw);
      if (!data || !data.id || !data.type) return;
      if (Date.now() - (data.ts || 0) > 60000) return;

      const btn = watchNowButton || seasonWatchBtn;
      if (!btn) return;
      const btnId = btn.getAttribute('data-entertainment-id');
      const btnType = btn.getAttribute('data-entertainment-type');
      if (btnId !== data.id || btnType !== data.type) return;

      setTimeout(function () { btn.click(); }, 400);
    } catch (_) {}
  })();

  if (watchNowButton) {
    watchNowButton.addEventListener('click', async function (e) {
      contentId = watchNowButton.getAttribute('data-contentid') || '';
      contentType = watchNowButton.getAttribute('data-contenttype') || '';
      contentVideoType = watchNowButton.getAttribute('content-video-type') || '';

      currentEpisodeName = watchNowButton.getAttribute('data-episode-name') || '';
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
          window.updateEpisodeNameDisplay(currentEpisodeName);
      }

      // console.log('=================================================='.contentId, contentType, contentVideoType);
      e.preventDefault()
      if (!isAuthenticated) {
        window.location.href = loginUrl // Redirect to login if not authenticated
        return // Stop further execution
      }
      await saveEntertainmentView(watchNowButton);
      // const entertainmentType = watchNowButton.getAttribute('data-entertainment-type');
      // if(entertainmentType == 'video'){
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }
      // }

      if (!isPrerollAlreadyShown()) {
        customAdPlayed = false;
        customAdAttempts = 0;
      } else {
        customAdPlayed = true;
      }

      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
        handleWatchButtonClick(watchNowButton);
      });
    })
  }

  document.addEventListener('click', async function (e) {
    const button = e.target.closest('.season-watch-btn');
    if (button) {
      contentId = button.getAttribute('data-contentid') || '';
      contentType = button.getAttribute('data-contenttype') || '';
      contentVideoType = button.getAttribute('content-video-type') || '';

      currentEpisodeName = button.getAttribute('data-episode-name') || '';
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
          window.updateEpisodeNameDisplay(currentEpisodeName);
      }

      // console.log('==================================================', contentId, contentType, contentVideoType);
    }

    if (button) {
      e.preventDefault();

      if (!isAuthenticated) {
        window.location.href = loginUrl;
        return;
      }

      await saveEntertainmentView(button);

      // Hide skip button using CSS class selector
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }

      if (!isPrerollAlreadyShown()) {
        customAdPlayed = false;
        customAdAttempts = 0;
      } else {
        customAdPlayed = true;
      }

      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
        handleWatchButtonClick(button);
      });
    }
  });

  // Reusable function to create quality selector
  function createQualitySelector(player, qualityOptions, subtitleInfo = [], baseUrl) {
    const existingQualitySelector = document.querySelector('.vjs-quality-selector')
    if (!existingQualitySelector && qualityOptions.length > 0) {
      const qualitySelector = document.createElement('div')
      qualitySelector.classList.add('vjs-quality-selector')

      const qualityDropdown = document.createElement('select')

      qualityOptions.forEach((option) => {
        const qualityOption = document.createElement('option')

        qualityOption.value = option.url.value // Use the URL for the quality option
        qualityOption.innerText = option.label // Display the label (e.g., "360p", "720p")
        qualityOption.setAttribute('data-type', option.url.type);
        qualityDropdown.appendChild(qualityOption)
      })

      function swapSource(targetPlayer, sourceDescriptor) {
        if (!targetPlayer || !sourceDescriptor) return;

        const resumeAt = Math.max(0, Number(targetPlayer.currentTime && targetPlayer.currentTime()) || 0);
        const wasPaused = typeof targetPlayer.paused === 'function' ? targetPlayer.paused() : false;

        targetPlayer.one('loadedmetadata', function () {
          try {
            if (resumeAt > 0) {
              targetPlayer.currentTime(resumeAt);
            }
            setSubtitle(targetPlayer, subtitleInfo);
            if (!wasPaused) {
              const playPromise = targetPlayer.play();
              if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function () {});
              }
            }
          } catch (e) {
            console.error('Error restoring position after quality change:', e);
          }
        });

        targetPlayer.src(sourceDescriptor);
        targetPlayer.load();
      }

      qualityDropdown.addEventListener('change', function () {
        const selectedQuality = this.value;

        const dataType = document.querySelector('.vjs-quality-selector select')
          ?.selectedOptions[0]?.getAttribute('data-type');

        const filteredOptions = qualityOptions.filter(option => option.url.type === 'Local' && dataType === option.url.type);

        if (filteredOptions.length > 0) {
          const option = filteredOptions[0];
          const videoPlayer = videojs('videoPlayer');
          swapSource(videoPlayer, { type: 'video/mp4', src: option.url.value });
        } else {
          fetch(`${baseUrl}/video/stream/${encodeURIComponent(selectedQuality)}`)
            .then(response => response.json())
            .then(data => {
              const { videoId, platform, url } = data;
              let descriptor = null;
              if (platform === 'youtube') {
                descriptor = { type: 'video/youtube', src: `https://www.youtube.com/watch?v=${videoId}` };
              } else if (platform === 'vimeo') {
                descriptor = { type: 'video/vimeo', src: `https://vimeo.com/${videoId}` };
              } else if (platform === 'hls') {
                descriptor = { type: 'application/x-mpegURL', src: url };
              } else if (url) {
                descriptor = { type: 'video/mp4', src: url };
              }
              if (descriptor) swapSource(player, descriptor);
            })
            .catch(error => console.error('Error playing video:', error));
        }
      });

      qualitySelector.appendChild(qualityDropdown)
      player.controlBar.el().appendChild(qualitySelector)
    }
  }

  function playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const datatype = watchNowButton?.getAttribute('data-type') || seasonWatchBtn?.getAttribute('data-type')

    if (datatype === 'Local') {
      const videoSource = document.querySelectorAll('#videoSource');

      videoSource.src = videoUrl;

      const videoPlayer = videojs('videoPlayer');
      videoPlayer.src({ type: 'video/mp4', src: videoUrl });
      setSubtitle(videoPlayer, subtitleInfo);
      videoPlayer.load();
      videoPlayer.play();

      // Use the reusable function
      createQualitySelector(player, qualityOptions, subtitleInfo, baseUrl);
    } else {
      // // Check device support BEFORE fetching/setting video source
      // CheckDeviceType().then(isDeviceSupported => {
      //   if (!isDeviceSupported) {
      //     $('#DeviceSupport').modal('show');
      //     player.pause && player.pause();
      //     return;
      //   }
        // If supported, fetch and set video source
        fetch(`${baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
          .then((response) => response.json())
          .then((data) => {
            setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo)
            player.load()
            setSubtitle(player, subtitleInfo);

            // Use the reusable function for quality selector
            createQualitySelector(player, qualityOptions, subtitleInfo, baseUrl);

            player.one('loadedmetadata', async function () {
              player.currentTime(lastWatchedTime)
              if (document.querySelector('#videoPlayer').getAttribute('data-movie-access') === 'free') {
                player.muted(true) // Mute the player for autoplay
                try {
                  await player.play() // Attempt to autoplay
                } catch (error) {
                  console.error('Error trying to autoplay:', error)
                }
              }
            })
          })
          .catch((error) => console.error('Error playing video:', error))
      // });
    }
  }

  function handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const planId = button.getAttribute('data-plan-id')
    fetch(`${baseUrl}/check-subscription/${planId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.isActive) {
          playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        } else {
          // Open the modal to show the user options for selecting or confirming a plan
          $('#DeviceSupport').modal('show')

          // Assuming you have a button inside the modal to proceed with payment
          document.querySelector('#confirmSubscriptionButton').addEventListener('click', function () {
            // Redirect to subscription plan after modal confirmation
            window.location.href = `${baseUrl}/subscription-plan`
          })
        }
      })
      .catch((error) => console.error('Error checking subscription:', error))
  }

  player.on('ended', async function () {
    if (isWatchHistorySaved) return

    const entertainmentId = currentEntertainmentId || watchNowButton?.getAttribute('data-entertainment-id') || seasonWatchBtn?.getAttribute('data-entertainment-id')
    const entertainmentType = currentEntertainmentType || watchNowButton?.getAttribute('data-entertainment-type') || seasonWatchBtn?.getAttribute('data-entertainment-type')
    const profileId = watchNowButton?.getAttribute('data-profile-id') || seasonWatchBtn?.getAttribute('data-profile-id')

    if (isAuthenticated && entertainmentId && entertainmentType && profileId) {
      // const isDeviceSupported = await CheckDeviceType()
      // if (!isDeviceSupported) {
      //   $('#DeviceSupport').modal('show')
      //   return
      // }

      const watchHistoryData = {
        entertainment_id: entertainmentId,
        entertainment_type: entertainmentType,
        profile_id: profileId
      }

      fetch(`${baseUrl}/api/save-watch-content`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(watchHistoryData)
      })
        .then((response) => response.json())
        .then((data) => {
          isWatchHistorySaved = true
        })
        .catch((error) => console.error('Error saving watch history:', error))
    }
  })

  window.addEventListener('beforeunload', function () {
    saveContinueWatchProgress(true)
  })

  // Function to initialize Kaltura Playkit player
  function initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId) {
    try {
      // console.log('Initializing Kaltura player:', { playerId, partnerId, uiConfId, entryId });

      // Ensure target element exists before initializing
      const targetEl = document.getElementById(playerId);
      if (!targetEl) {
        console.warn('Kaltura target not yet in DOM, retrying...');
        setTimeout(() => initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId), 50);
        return;
      }

      // Validate parameters
      if (!entryId || entryId.length < 3) {
        throw new Error('Invalid entry ID: ' + entryId);
      }

      if (!partnerId || !uiConfId) {
        throw new Error('Invalid partner ID or uiConf ID');
      }

      const kalturaPlayer = KalturaPlayer.setup({
        targetId: playerId,
        provider: {
          partnerId: parseInt(partnerId),
          uiConfId: parseInt(uiConfId)
        }
      });

      // console.log('Loading media with entry ID:', entryId);
      kalturaPlayer.loadMedia({ entryId: entryId });

      // Trigger loadedmetadata event after Kaltura player is ready
      kalturaPlayer.addEventListener('loadedmetadata', () => {
        // console.log('Kaltura player metadata loaded');
        // Find the video.js player and trigger the event
        const videoPlayer = document.getElementById('videoPlayer');
        if (videoPlayer && videoPlayer.player) {
          videoPlayer.player.trigger('loadedmetadata');
        }
      });

      // Also listen for play event
      kalturaPlayer.addEventListener('play', () => {
        // console.log('Kaltura player started playing');
      });

      // Listen for error events
      kalturaPlayer.addEventListener('error', (error) => {
        console.error('Kaltura player error:', error);
      });

      // Listen for media load events
      kalturaPlayer.addEventListener('mediaLoadError', (error) => {
        console.error('Kaltura media load error:', error);
      });

      // console.log('Kaltura player initialized successfully');
    } catch (e) {
      console.error('Error initializing Kaltura player:', e.message);
    }
  }

  function setVideoSource(player, platform, videoId, url = '', adTagUrl = '') {
    const videoEl = document.getElementById('videoPlayer');
    const vimeoIframe = document.getElementById('vimeoIframe');
    if (videoEl) videoEl.style.display = '';
    if (vimeoIframe) vimeoIframe.style.display = 'none';

    // Always hide both players first
    if (videoEl) videoEl.style.display = 'none';
    if (vimeoIframe) vimeoIframe.style.display = 'none';
    if (platform === 'youtube') {
      player.src({
        type: 'video/youtube',
        src: `https://www.youtube.com/watch?v=${videoId}`
      });
    } else if (platform === 'hls') {
      player.src({ type: 'application/x-mpegURL', src: url });

      if (adTagUrl) {
        // ✅ Initialize IMA Ads only here (non-YouTube)
        player.ima({
          id: 'videoPlayer',
          adTagUrl: adTagUrl
        });
        player.ima.requestAds();
      }
    } else if (platform === 'local') {
      // player.src({ type: , src: url });
      player.src({ type: 'video/mp4', src: url });

      if (adTagUrl) {
        player.ima({
          id: 'videoPlayer',
          adTagUrl: adTagUrl
        });
        player.ima.requestAds();
      }
    } else if (platform === 'embed' || platform === 'embedded') {
      // Pause the player
      if (player && typeof player.pause === 'function') {
        player.pause();
      }
      // Hide native video element safely
      const nativeVideoEl = player.el().querySelector('video');
      if (nativeVideoEl) {
        nativeVideoEl.style.display = 'none';
      }
      // Remove existing iframe overlays if any
      const existingOverlay = player.el().querySelector('.vjs-iframe-overlay');
      if (existingOverlay) {
        existingOverlay.remove();
      }
      // Create a fresh overlay div with iframe
      const iframeOverlay = document.createElement('div');
      iframeOverlay.className = 'vjs-iframe-overlay';
      Object.assign(iframeOverlay.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        zIndex: '10',
      });

            // Check if it's a Kaltura embed URL
      if (url.includes('kaltura.com')) {
        // console.log('Loading Kaltura Playkit embed:', url);

        // Extract entry ID from URL if it's a full URL
        let entryId = videoId;
        if (url.includes('entry_id=')) {
          const match = url.match(/entry_id=([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        } else if (url.includes('entryId=')) {
          const match = url.match(/entryId=([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        } else if (url.includes('entryId:')) {
          const match = url.match(/entryId:([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        }

        // Extract partner ID and uiConf ID from URL
        let partnerId = 650742; // default
        let uiConfId = 51878872; // default

        const partnerMatch = url.match(/partner_id=(\d+)/);
        if (partnerMatch) {
          partnerId = partnerMatch[1];
        } else {
          const partnerMatch2 = url.match(/p\/(\d+)/);
          if (partnerMatch2) {
            partnerId = partnerMatch2[1];
          }
        }

        const uiConfMatch = url.match(/uiconf_id=(\d+)/);
        if (uiConfMatch) {
          uiConfId = uiConfMatch[1];
        }

        // If we still don't have a valid entry ID, try to extract from videoId
        if (!entryId || entryId === 'undefined' || entryId === 'null' || entryId === '') {
          // Try to extract entry ID from videoId if it looks like a Kaltura entry ID
          if (videoId && videoId.includes('_')) {
            entryId = videoId;
          }
        }

        // Clean the entry ID - remove any HTML or extra characters
        if (entryId) {
          entryId = entryId.replace(/[^\w_-]/g, ''); // Only keep alphanumeric, underscore, and hyphen
        }

        // console.log('Kaltura parameters extracted:', { entryId, partnerId, uiConfId, url, videoId });
        // console.log('Original URL being processed:', url);

        // Validate entry ID
        if (!entryId || entryId.length < 3) {
          console.error('Invalid Kaltura entry ID:', entryId);
          return;
        }

        // Create unique player ID
        const playerId = 'kaltura_player_' + Date.now();

        iframeOverlay.innerHTML = `
          <div id="${playerId}" style="width: 100%; height: 100%;"></div>
        `;

        // Append the container BEFORE loading script to ensure it exists
        player.el().appendChild(iframeOverlay);

        // Load Kaltura Playkit script if not already loaded
        if (!window.KalturaPlayer) {
          const script = document.createElement('script');
          script.src = `https://cdnapisec.kaltura.com/p/${partnerId}/embedPlaykitJs/uiconf_id/${uiConfId}`;
          script.onload = () => {
            initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId);
          };
          document.head.appendChild(script);
        } else {
          initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId);
        }
      } else {
        // For other embed URLs
        // console.log('Loading generic embed:', url);
        iframeOverlay.innerHTML = `
          <iframe
            src="${url}"
            width="100%"
            height="100%"
            frameborder="0"
            allow="autoplay; fullscreen; picture-in-picture"
            style="border:0;"
            allowfullscreen>
          </iframe>
        `;

        // Append to the Video.js player container
        player.el().appendChild(iframeOverlay);
      }

      // Trigger loadedmetadata event for embed content (only for non-Kaltura embeds)
      if (!url.includes('kaltura.com')) {
        setTimeout(() => {
          player.trigger('loadedmetadata');
          // console.log('Embed iframe loaded');
        }, 100);
      }
    } else if (platform === 'vimeo') {
      playVimeoWithPreRoll(player, videoId, url, adTagUrl)
    }

    // Handle other platforms (YouTube, local, HLS)
    if (videoEl) videoEl.style.display = 'block';
    if (vimeoIframe) {
      vimeoIframe.src = ''; // cleanup
      vimeoIframe.style.display = 'none';
    }
  }

  // Dedicated function for Vimeo + pre-roll
  function playVimeoWithPreRoll(player, videoId, url = '', adTagUrl = '') {
    // Stop any running video
    if (player && typeof player.reset === 'function') {
      player.pause();
      player.reset();
    }
    const vimeoContainer = document.getElementById('vimeoContainer');
    const vimeoIframe = document.getElementById('vimeoIframe');
    if (vimeoContainer) vimeoContainer.style.display = 'none';
    if (vimeoIframe) {
      vimeoIframe.style.display = 'none';
      vimeoIframe.src = '';
    }
    const videoEl = document.getElementById('videoPlayer');
    if (videoEl) videoEl.style.display = 'none';

    // Helper to show Vimeo after all ads
    function showVimeo() {
      stopAllVideoPlayers();
      if (vimeoIframe) {
        vimeoIframe.src = `https://player.vimeo.com/video/${videoId}?autoplay=1&muted=0`;
        vimeoIframe.style.display = 'block';
        videoEl.style.display = 'none';
      }
      if (vimeoContainer) vimeoContainer.style.display = 'block';
    }

    // 1. Check for custom ads first
    const paramsCustom = new URLSearchParams();
    if (typeof contentId !== 'undefined' && contentId) paramsCustom.append('content_id', contentId);
    if (typeof contentType !== 'undefined' && contentType) paramsCustom.append('type', contentType);
    if (typeof currentCategoryId !== 'undefined' && currentCategoryId) paramsCustom.append('category_id', currentCategoryId);
    const customApiUrl = `${baseUrl}/api/custom-ads/get-active?${paramsCustom.toString()}`;

    fetch(customApiUrl)
      .then(res => res.json())
      .then(customData => {
        let customAd = null;
        if (customData.success && Array.isArray(customData.data)) {
          customAd = customData.data.find(item => item.placement === 'player' && item.status == 1);
        }
        if (customAd) {
          // Play custom ad, then VAST, then Vimeo
          playCustomAd(customAd, () => {
            playVastAndVimeo();
          });
        } else {
          // No custom ad, just VAST then Vimeo
          playVastAndVimeo();
        }
      });

    // 2. Play VAST pre-rolls, then Vimeo
    function playVastAndVimeo() {
      //skip the vast ads for the vimeo content
      // const params = new URLSearchParams();
      // if (typeof contentId !== 'undefined' && contentId) params.append('content_id', contentId);
      // if (typeof contentType !== 'undefined' && contentType) params.append('type', contentType);
      // if (typeof contentVideoType !== 'undefined' && contentVideoType) params.append('video_type', contentVideoType);
      // if (typeof currentCategoryId !== 'undefined' && currentCategoryId) params.append('category_id', currentCategoryId);
      // if (typeof currentEpisodeId !== 'undefined' && currentEpisodeId) params.append('episode_id', currentEpisodeId);
      // fetch(`${baseUrl}/api/vast-ads/get-active?${params.toString()}`)
      //   .then(res => res.json())
      //   .then(response => {
      //     adQueue = response.data || [];
      //     const preRollAds = adQueue.filter(ad => ad.type === 'pre-roll');
      //     if (preRollAds.length > 0) {
      //       playAdsByType('pre-roll', showVimeo);
      //     } else {
      //       showVimeo();
      //     }
      //   });
      // console.log('skip the vast ads for the vimeo content');
      showVimeo();
    }

    // 3. Play custom ad (image/video/youtube)
    function playCustomAd(ad, onFinish) {
      // console.log('Playing custom ad:', ad.type);

      // Hide Vimeo container during custom ad
      if (vimeoContainer) vimeoContainer.style.display = 'none';
      const modal = document.getElementById('customAdModal');
      const content = document.getElementById('customAdContent');

      if (!modal || !content) {
        console.error('Custom ad modal elements not found');
        if (typeof onFinish === 'function') onFinish();
        return;
      }

      let adHtml = '';
      try {
        if (ad.type === 'image') {
          if (ad.redirect_url) {
            adHtml = `<a href="${ad.redirect_url}" target="_blank"><img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" /></a>`;
          } else {
            adHtml = `<img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" />`;
          }
        } else if (ad.type === 'video') {
          if (/youtu\.?be/.test(ad.media)) {
            let videoId = '';
            const ytMatch = ad.media.match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
            if (ytMatch && ytMatch[1]) {
              videoId = ytMatch[1];
            }
            if (videoId) {
              adHtml = `<div style="position:relative;width:100%;height:100%;"><iframe id="customAdVideo" width="100%" height="100%" src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.7);"></iframe>`;
              if (ad.redirect_url) {
                adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
              }
              adHtml += `</div>`;
            } else {
              adHtml = `<div style="color:#fff;">Invalid YouTube URL</div>`;
            }
          } else if(ad.url_type  == "url") {
            adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${ad.media}" type="video/mp4"></video>`;
            if (ad.redirect_url) {
              adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
            }
            adHtml += `</div>`;
          } else {
            adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${baseUrl}${ad.media}" type="video/mp4"></video>`;
            if (ad.redirect_url) {
              adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
            }
            adHtml += `</div>`;
          }
        }
      } catch (error) {
        console.error('Error creating custom ad HTML:', error);
        adHtml = `<div style="color:#fff; padding:20px; text-align:center;">Error loading ad: ${error.message}</div>`;
      }

      // Clear existing content
      content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
      content.insertAdjacentHTML('afterbegin', adHtml);
      modal.style.display = 'flex';

      const closeBtn = document.getElementById('customAdCloseBtn');
      if (closeBtn) {
        closeBtn.onclick = () => closeCustomAdModal();
      }

      if (ad.type === 'image') {
        setTimeout(() => {
          if (modal.style.display === 'flex') {
            closeCustomAdModal();
          }
        }, 5000);
      }

      if (ad.type === 'video') {
        const adVideo = document.getElementById('customAdVideo');
        if (adVideo) {
          adVideo.onended = () => {
            // console.log('Custom video ad ended');
            closeCustomAdModal();
          };
          adVideo.onerror = (e) => {
            console.error('Custom video ad error:', e);
            closeCustomAdModal();
          };
        }
      }

      function closeCustomAdModal() {
        // console.log('Closing custom ad modal');
        modal.style.display = 'none';
        content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
        if (typeof onFinish === 'function') onFinish();
      }

      // Add click handler for overlay if present
      if (ad.redirect_url) {
        setTimeout(() => {
          const overlay = document.getElementById('adClickOverlay');
          if (overlay) {
            overlay.onclick = () => {
              window.open(ad.redirect_url, '_blank');
            };
          }
        }, 0);
      }
    }
  }


  class SkipTrainerButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.addClass('vjs-skip-trainer-button');
      // Get video element references
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.watchNowButton = document.getElementById('watchNowButton');
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');
      this.baseUrl = options.baseUrl;
      this.player_ = player;
      // Get entertainment data using data-entertainment-id instead of data-video-url
      this.entertainmentId = this.watchNowButton?.getAttribute('data-entertainment-id') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-id');
      this.entertainmentType = this.watchNowButton?.getAttribute('data-entertainment-type') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-type');
      this.episodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');
      // Add timeupdate listener to control visibility
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);
      // Initial visibility
      this.trailerSkipped = false; // Add flag to track if trailer was skipped
      this.handleTimeUpdate();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: 'Skip Trailer',
        className: 'vjs-skip-trainer-button vjs-control'
      });
    }

    handleTimeUpdate() {
      // Show only if currentTime < movieStartTime and trailer not skipped
      if (this.player_.currentTime() < this.movieStartTime && !this.trailerSkipped) {
        this.show();
        // Hide NextEpisodeButton if present
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn) nextBtn.hide();
      } else {
        this.hide();
        // Show NextEpisodeButton if it should be visible
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn && typeof nextBtn.updateVisibility === 'function') nextBtn.updateVisibility();
      }
    }

    handleClick() {
      // Get the video URL and quality options from watch now button
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) {
        console.error('Watch button not found');
        return;
      }

      saveEntertainmentView(button);

      const videoUrl = button.getAttribute('data-video-url');
      const qualityOptionsData = button.getAttribute('data-quality-options');
      const qualityOptions = qualityOptionsData ?
        Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url })) : [];
      const accessType = button.getAttribute('data-movie-access');
      const planId = button.getAttribute('data-plan-id');

      // Check episode purchase status if it's pay-per-view
      if (this.episodeId) {
        fetch(`${this.baseUrl}/api/check-episode-purchase?episode_id=${this.episodeId}`)
          .then(response => response.json())
          .then(data => {
            if (!data.is_purchased) {
              // Redirect to purchase page
              window.location.href = `${this.baseUrl}/payment-form/pay-per-view?type=episode&id=${this.episodeId}`;
              return;
            }
          })
          .catch(error => {
            console.error('Error checking episode purchase:', error);
          });
      }

      // Check authentication and device support for paid content
      if (accessType === 'paid') {
        checkAuthenticationAndDeviceSupport().then(canPlay => {
          if (!canPlay) {
            $('#DeviceSupport').modal('show');
            return;
          }

          // If device is supported, proceed with subscription check
          if (planId) {
            CheckSubscription(planId).then(isActive => {
              if (isActive) {
                this._playMovie(videoUrl, qualityOptions);
              } else {
                $('#DeviceSupport').modal('show');
                // Optionally add click handler for confirmSubscriptionButton
                const confirmBtn = document.querySelector('#confirmSubscriptionButton');
                if (confirmBtn) {
                  confirmBtn.addEventListener('click', () => {
                    window.location.href = `${this.baseUrl}/subscription-plan`;
                  }, { once: true });
                }
              }
            }).catch(error => {
              console.error('Error checking subscription:', error);
              // $('#DeviceSupport').modal('show');
            });
          }
        }).catch(error => {
          console.error('Error checking device support:', error);
          // $('#DeviceSupport').modal('show');
        });
      } else {
        // Free or no plan required, play movie
        this._playMovie(videoUrl, qualityOptions);
      }
    }

    _playMovie(videoUrl, qualityOptions) {
      // contentVideoType = contentType;
      const button = this.watchNowButton || this.seasonWatchBtn;
      contentId = button.getAttribute('data-contentid') || '';
      contentType = button.getAttribute('data-contenttype') || '';
      contentVideoType = button.getAttribute('content-video-type') || '';

      currentEpisodeName = button.getAttribute('data-episode-name') || '';
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
          window.updateEpisodeNameDisplay(currentEpisodeName);
      }

      // console.log('==================================================', contentId, contentType, contentVideoType);
      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
      });
      const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]');
      const dataType = button.getAttribute('data-type'); // e.g., "Local" or "Remote"



      if (dataType === 'Local') {
        // Set local video source directly
        this.player_.src({ type: 'video/mp4', src: videoUrl });
        // Add subtitle tracks if available
        if (subtitleInfo && subtitleInfo.length > 0) {
          // Remove any existing subtitle tracks
          const existingTracks = this.player_.textTracks();
          for (let i = existingTracks.length - 1; i >= 0; i--) {
            this.player_.removeRemoteTextTrack(existingTracks[i]);
          }
          // Add new subtitle tracks
          subtitleInfo.forEach(subtitle => {
            this.player_.addRemoteTextTrack({
              kind: 'subtitles',
              src: subtitle.subtitle_file,
              srclang: subtitle.language_code,
              label: subtitle.language,
              default: subtitle.is_default === 1
            }, false);
          });

          setTimeout(() => {
            const tracks = this.player_.textTracks();
            for (let i = 0; i < tracks.length; i++) {
              if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
                tracks[i].mode = 'showing';
              } else {
                tracks[i].mode = 'disabled';
              }
            }
          }, 0);

        }

        this.player_.load();

        // Use the reusable function for quality selector
        createQualitySelector(this.player_, qualityOptions, subtitleInfo, this.baseUrl);

        this.player_.one('loadedmetadata', () => {
          this.player_.currentTime(0);
          this.player_.muted(true);
          this.player_.play()
            .then(() => {
              this.trailerSkipped = true;
              this.hide();
            })
            .catch(error => console.error('Error playing movie:', error));
        });
      } else {
        // Remote/encrypted video: use your existing logic
        fetch(`${this.baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
          .then((response) => response.json())
          .then((data) => {
            setVideoSource(this.player_, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo);
            // Add subtitle tracks if available
            if (subtitleInfo && subtitleInfo.length > 0) {
              // Remove any existing subtitle tracks
              const existingTracks = this.player_.textTracks();
              for (let i = existingTracks.length - 1; i >= 0; i--) {
                this.player_.removeRemoteTextTrack(existingTracks[i]);
              }
              // Add new subtitle tracks
              subtitleInfo.forEach(subtitle => {
                this.player_.addRemoteTextTrack({
                  kind: 'subtitles',
                  src: subtitle.subtitle_file,
                  srclang: subtitle.language_code,
                  label: subtitle.language,
                  default: subtitle.is_default === 1
                }, false);
              });

              setTimeout(() => {
                const tracks = this.player_.textTracks();
                for (let i = 0; i < tracks.length; i++) {
                  if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
                    tracks[i].mode = 'showing';
                  } else {
                    tracks[i].mode = 'disabled';
                  }
                }
              }, 0);

            }
            this.player_.load();

            // Use the reusable function for quality selector
            createQualitySelector(this.player_, qualityOptions, subtitleInfo, this.baseUrl);

            this.player_.one('loadedmetadata', () => {
              this.player_.currentTime(0);
              this.player_.muted(true);
              this.player_.play()
                .then(() => {
                  this.trailerSkipped = true;
                  this.hide();
                })
                .catch(error => console.error('Error playing movie:', error));
            });
          })
          .catch((error) => console.error('Error loading video:', error));
      }
    }
  }

  // Register the new component
  videojs.registerComponent('SkipTrainerButton', SkipTrainerButton);


  class PreviousEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText('Previous Episode');
      this.addClass('vjs-previous-episode-button');

      // Get button references
      this.watchNowButton = document.getElementById('watchNowButton');
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');

      // Get current episode info
      this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');

      // Show button only if it's not the first episode
      this.updateVisibility();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: '<span class="vjs-icon-previous-item"></span>',
        className: 'vjs-previous-episode-button vjs-control vjs-button'
      });
    }

    updateVisibility() {
      const currentId = parseInt(this.currentEpisodeId);
      if (!this.currentEpisodeId || currentId <= 1) {
        this.hide();
      } else {
        this.show();
      }
    }

    handleClick() {
      // Get current episode button
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;

      // Get previous episode data
      const previousEpisodeId = parseInt(this.currentEpisodeId) - 1;
      const previousEpisodeButton = document.querySelector(`[data-episode-id="${previousEpisodeId}"]`);

      if (previousEpisodeButton) {
        // Store current time before switching
        const currentTime = this.player_.currentTime();

        // Update current episode ID before click
        this.currentEpisodeId = previousEpisodeId;

        // Trigger click on previous episode button
        previousEpisodeButton.click();

        currentEpisodeName = previousEpisodeButton.getAttribute('data-episode-name') || '';
        // Update episode name display
        if (window.updateEpisodeNameDisplay) {
            window.updateEpisodeNameDisplay(currentEpisodeName);
        }

        // Update visibility after changing episode
        this.updateVisibility();

        // Handle video source change
        this.player_.one('loadedmetadata', () => {
          // Reset playback state
          this.player_.muted(true);
          this.player_.currentTime(0);
          this.player_.play().catch(error => {
            console.error('Error playing previous episode:', error);
          });
        });
      }
    }

    // Update episode ID when source changes
    updateEpisodeId(newId) {
      this.currentEpisodeId = newId;
      this.updateVisibility();
    }
  }

  // Register the component
  videojs.registerComponent('PreviousEpisodeButton', PreviousEpisodeButton);




  class NextEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText('Next Episode');
      this.addClass('vjs-next-episode-button');
      this.watchNowButton = document.getElementById('watchNowButton');

      // Get initial episode ID from any season watch button
      const seasonWatchBtn = document.querySelector('.season-watch-btn');
      if (seasonWatchBtn) {
        this.currentEpisodeId = seasonWatchBtn.getAttribute('data-episode-id');
      } else {
        this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id');
      }

      this.totalEpisodes = this.getTotalEpisodes();
      this.player_ = player;
      // Get movieStartTime for trailer logic
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);

      // Add click event listener to all season watch buttons
      document.querySelectorAll('.season-watch-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          // Get episode ID from the clicked button
          const episodeId = e.currentTarget.getAttribute('data-episode-id');
          if (episodeId) {
            this.currentEpisodeId = episodeId;
            this.updateVisibility();
          }
        });
      });

      this.removeExistingButtons();
      this.updateVisibility();
      this.removeExistingButtons();
      this.updateVisibility();
    }

    removeExistingButtons() {
      const existingButtons = document.querySelectorAll('.vjs-next-episode-button');
      existingButtons.forEach(button => {
        if (button !== this.el_) {
          button.remove();
        }
      });
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: 'Next Episode',
        className: 'vjs-next-episode-button vjs-control vjs-button p-2'
      });
    }

    getTotalEpisodes() {
      // Use the same filtering logic as in handleClick
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      return episodeIds.length;
    }

    handleTimeUpdate() {
      if (!this.player_) return;

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      // Calculate 20% threshold
      const showThreshold = duration * 0.2;
      const trailerThreshold = Math.min(this.movieStartTime, duration * 0.3);

      // Hide if in trailer section
      if (currentTime < trailerThreshold) {
        this.hide();
        const skipBtn = this.player_.controlBar.getChild('SkipTrainerButton');
        if (skipBtn) skipBtn.show();
        return;
      }

      const timeLeft = duration - currentTime;

      // Show button when:
      // 1. Time left is less than 20% of total duration
      // 2. There is a next episode
      // 3. It's not the last episode
      if (timeLeft <= showThreshold && this.hasNextEpisode() && !this.isLastEpisode()) {
        this.show();
      } else {
        this.hide();
      }
    }

    isLastEpisode() {
      // Use the filtered episodeIds array
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      return currentIndex === episodeIds.length - 1;
    }

    hasNextEpisode() {
      // Get all episode IDs in the DOM
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      // If the first and second elements are the same, remove the first one
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      const nextId = episodeIds[currentIndex + 1];
      // console.log('Current index:', currentIndex, 'Next episode ID:', nextId);
      return !!nextId;
    }

    updateVisibility() {
      if (!this.player_) return;

      // Basic checks
      if (!this.currentEpisodeId || !this.hasNextEpisode() || this.isLastEpisode()) {
        this.hide();
        return;
      }

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      // Calculate 20% threshold
      const showThreshold = duration * 0.2;

      const timeLeft = duration - currentTime;

      // Show button if time left is less than 20% of total duration
      if (timeLeft <= showThreshold) {
        this.show();
      } else {
        this.hide();
      }
    }

    async handleClick() {
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;

      // Get all episode IDs in the DOM and log them
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      // If the first and second elements are the same, remove the first one
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      // console.log('All episode IDs in DOM:', episodeIds);
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      const nextId = episodeIds[currentIndex + 1];


      // Find the corresponding button for nextId
      const filteredButtons = episodeButtons;
      if (episodeButtons.length > 1 && episodeButtons[0].getAttribute('data-episode-id') === episodeButtons[1].getAttribute('data-episode-id')) {
        filteredButtons.shift();
      }
      const nextEpisodeButton = filteredButtons[currentIndex + 1];

      if (nextEpisodeButton) {
        const wasPlaying = !this.player_.paused();
        // Do NOT set this.currentEpisodeId = nextId yet

        // --- Pay-per-view logic start ---
        const accessType = nextEpisodeButton.getAttribute('data-movie-access');
        const episodeId = nextEpisodeButton.getAttribute('data-episode-id');
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        if (accessType === 'pay-per-view') {
          try {
            const response = await fetch(`${baseUrl}/api/check-episode-purchase?episode_id=${episodeId}&t=${Date.now()}`);
            const data = await response.json();
            if (data.is_purchased == false) {
              // Not purchased: redirect to payment form
              window.location.href = `${baseUrl}/payment-form/pay-per-view?type=episode&id=${episodeId}`;
              return; // STOP here, do not continue!
            }
            // else: purchased, continue to play
          } catch (error) {
            console.error('[NextEpisodeButton] Error checking episode purchase:', error);
            return;
          }
        }

        // Only now, after passing the check, update the current episode and play
        this.currentEpisodeId = nextId;
        nextEpisodeButton.click();

        currentEpisodeName = nextEpisodeButton.getAttribute('data-episode-name') || '';
        // Update episode name display
        if (window.updateEpisodeNameDisplay) {
            window.updateEpisodeNameDisplay(currentEpisodeName);
        }

        this.player_.one('loadedmetadata', () => {
          this.player_.muted(true);
          this.player_.currentTime(0);
          this.updateVisibility();

          if (wasPlaying) {
            this.player_.play().catch(error => {
              console.error('Error playing next episode:', error);
            });
          }
        });
      } else {
        console.warn('No next episode available');
        this.hide();
      }
    }

    dispose() {
      if (this.player_) {
        this.player_.off('timeupdate', this.timeUpdateHandler);
      }
      super.dispose();
    }
  }
  videojs.registerComponent('NextEpisodeButton', NextEpisodeButton);


  function timeStringToSeconds(timeString) {
    const [hours, minutes, seconds] = timeString.split(':').map(Number)
    return hours * 3600 + minutes * 60 + seconds
  }

  // ADS CODE
  // console.log("contentId", contentId);
  // console.log("contentType", contentType);
  // console.log("currentCategoryId", currentCategoryId);



  var adQueue = [];
  var overlayAds = [];
  var playbackInterval = null;
  var midRollTime = 0;
  var midRollPlayed = false;
  var forcedMidRollPending = false;
  var postRollPlayed = false;
  let overlayAdsScheduled = false;
  var currentAd = null;
  var skipButton = null;
  var skipTimeout = null;
  let customAdPlayed = false;
  let blockPlay = false;
  let customAdShowing = false;
  let customAdAttempts = 0;
  const MAX_AD_ATTEMPTS = 3;
  let customAdsDisabled = false; // Nuclear option to disable custom ads

  const prerollPlayedForContent = new Set();
  function contentKey(id, type) {
    return `${id || ''}:${type || ''}`;
  }
  function isPrerollAlreadyShown() {
    return prerollPlayedForContent.has(contentKey(contentId, contentType));
  }
  function markPrerollShown() {
    prerollPlayedForContent.add(contentKey(contentId, contentType));
  }

  function debugLog(message, data = null) {
    const timestamp = new Date().toISOString();
    // console.log(`[${timestamp}] ${message}`, data ?? '');
  }

  function showAdLoader() { }
  function hideAdLoader() { }

  function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
  }

  function parseTimeToSeconds(timeString) {
    if (!timeString) return 0;
    const parts = timeString.split(':');
    if (parts.length === 2) {
      return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }
    return parseInt(timeString);
  }

  function createSkipButton() {
    if (skipButton) {
      skipButton.remove();
    }

    skipButton = document.createElement('div');
    skipButton.className = 'vjs-skip-ad-button';
    skipButton.innerHTML = 'Skip Ad';
    skipButton.style.cssText = `
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    z-index: 1001;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease;
  `;

    skipButton.addEventListener('mouseenter', () => {
      skipButton.style.background = 'rgba(255, 0, 0, 0.9)';
    });

    skipButton.addEventListener('mouseleave', () => {
      skipButton.style.background = 'rgba(0, 0, 0, 0.8)';
    });

    skipButton.addEventListener('click', () => {
      debugLog('Skip button clicked');
      skipAd();
    });

    return skipButton;
  }

  function showSkipButton(afterSeconds) {
    if (!currentAd || !currentAd.enable_skip) return;

    debugLog('Setting up skip button', {
      skipAfter: currentAd.skip_after,
      skipAfterSeconds: afterSeconds
    });

    skipTimeout = setTimeout(() => {
      const button = createSkipButton();
      player.el().appendChild(button);
      debugLog('Skip button shown after', afterSeconds + ' seconds');
    }, afterSeconds * 1000);
  }

  function hideSkipButton() {
    if (skipButton) {
      skipButton.remove();
      skipButton = null;
    }
    if (skipTimeout) {
      clearTimeout(skipTimeout);
      skipTimeout = null;
    }
    currentAd = null;
  }

  function hideIMASkipButton() {
    const adContainers = document.querySelectorAll('.ima-ad-container, .vjs-ima-ad-container');
    adContainers.forEach(container => {
      container.querySelectorAll('button, div').forEach(el => {
        if (
          el.textContent && el.textContent.trim().toLowerCase().includes('skip ad') &&
          el.offsetParent !== null
        ) {
          el.style.display = 'none';
          el.style.visibility = 'hidden';
          el.style.opacity = '0';
          el.style.pointerEvents = 'none';
          el.style.width = '0';
          el.style.height = '0';
        }
      });
    });
  }

  function observeAndRemoveIMASkipButton() {
    const adContainer = document.querySelector('.ima-ad-container, .vjs-ima-ad-container');
    if (!adContainer) return;

    const observer = new MutationObserver(() => {
      hideIMASkipButton();
    });

    observer.observe(adContainer, { childList: true, subtree: true });
    player.one('adend', () => observer.disconnect());
  }

  function skipAd() {
    debugLog('Custom skip button clicked');
    hideSkipButton();

    try {
      // Pause ad playback
      player.pause();

      // Wait a tiny bit and force end the ad manually
      setTimeout(() => {
        debugLog('Force ending ad via adend trigger');
        player.trigger('adend');
      }, 100); // You can keep this short (100ms)
    } catch (e) {
      debugLog('Error during manual skip', e);
      player.trigger('adend');
    }
  }


  function markAdCue(seconds, label = 'Ad') {
    const duration = player.duration();
    if (!duration || isNaN(duration)) return;

    const percent = (seconds / duration) * 100;
    const cue = document.createElement('div');
    cue.className = 'vjs-ad-cue';
    cue.title = label;
    cue.style.left = `${percent}%`;

    const progressControl = player.el().querySelector('.vjs-progress-holder');
    if (progressControl) {
      progressControl.appendChild(cue);
    }
  }

  function playAdsByType(type, onComplete = () => { }) {
    const ads = adQueue.filter(a => a.type === type);
    if (!ads.length) return onComplete();

    debugLog(`Starting ${ads.length} ${type} ads`, ads);
    let index = 0;

    const playNextAd = () => {
      if (index >= ads.length) return onComplete();

      const ad = ads[index];
      currentAd = ad;
      debugLog(`Playing ${type} ad #${index + 1}`, ad);

      try {
        showAdLoader();

        const vastUrl = ad.url;
        debugLog('Loading VAST XML', { vastUrl });

        player.ima.initializeAdDisplayContainer(); // ✅ ensure fresh IMA container
        player.ima.changeAdTag(vastUrl);
        player.ima.requestAds();
        player.ima.playAdBreak();

        player.one('adstart', () => {
          debugLog(`${type} ad #${index + 1} started`);
          hideAdLoader();
          observeAndRemoveIMASkipButton();

          if (ad.enable_skip && ad.skip_after) {
            const skipAfterSeconds = parseTimeToSeconds(ad.skip_after);
            showSkipButton(skipAfterSeconds);
          }
        });

        player.one('adend', () => {
          debugLog(`${type} ad #${index + 1} ended`);
          hideSkipButton();
          index++;
          playNextAd();
        });

        player.one('adserror', (e) => {
          debugLog(`${type} ad #${index + 1} error`, e);
          hideSkipButton();
          index++;
          playNextAd();
        });

        player.one('adskip', () => {
          debugLog(`${type} ad #${index + 1} skipped`);
          hideSkipButton();
          index++;
          playNextAd();
        });

      } catch (e) {
        debugLog(`Error playing ${type} ad`, e);
        hideSkipButton();
        index++;
        playNextAd();
      }
    };

    playNextAd();
  }

  function scheduleOverlayAds() {
    if (!player || overlayAdsScheduled) return;
    const duration = player.duration();

    // Better duration validation
    if (!duration || isNaN(duration) || duration <= 0) {
      // console.log('Duration not available for overlay ads, retrying...');
      setTimeout(() => scheduleOverlayAds(), 1000);
      return;
    }

    const availableOverlayAds = adQueue.filter(a => a.type === 'overlay');
    if (availableOverlayAds.length === 0) return;

    overlayAdsScheduled = true;
    overlayAds = availableOverlayAds;

    const overlaySchedule = [
      { percent: 10, shown: false },
      { percent: 65, shown: false }
    ];

    overlayAds.forEach((ad, index) => {
      if (overlaySchedule[index]) {
        const time = Math.floor((overlaySchedule[index].percent / 100) * duration);
        overlaySchedule[index].time = time;
        markAdCue(time, `Overlay Ad #${index + 1}`);
      }
    });

    const overlayCheckInterval = setInterval(() => {
      const current = player.currentTime();

      overlaySchedule.forEach((schedule, index) => {
        if (!schedule.shown && current >= schedule.time && current < schedule.time + 2) {
          schedule.shown = true;
          const ad = overlayAds[index];
          if (ad) showOverlayAd(ad.url, index + 1);
        }
      });

      if (overlaySchedule.every((s, i) => i >= overlayAds.length || s.shown)) {
        clearInterval(overlayCheckInterval);
      }
    }, 1000);
  }

  function showOverlayAd(url, index = 1) {
    debugLog(`Showing overlay ad #${index}`, url);

    const overlay = document.createElement('div');
    overlay.className = 'custom-overlay-ad';
    overlay.style.position = 'absolute';
    overlay.style.bottom = '10%';
    overlay.style.left = '10%';
    overlay.style.width = '80%';
    overlay.style.height = '100px';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.8)';
    overlay.style.color = '#fff';
    overlay.style.zIndex = 1000;
    // overlay.style.padding = '10px';
    // overlay.style.borderRadius = '8px';
    overlay.innerHTML = `
    <iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>
    <button style="position:absolute; top:5px; right:5px; background:#f00; color:#fff; border:none; padding:5px; cursor:pointer;">&times;</button>
  `;

    overlay.querySelector('button').onclick = () => overlay.remove();
    player.el().appendChild(overlay);
    setTimeout(() => overlay.remove(), 15000);
  }


      function showCustomAdThenPlayMain(afterAdCallback) {
    // Nuclear option - disable custom ads if they're causing problems
    if (customAdsDisabled) {
      console.warn('Custom ads disabled due to previous errors');
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    // Prevent infinite loops
    if (customAdAttempts >= MAX_AD_ATTEMPTS) {
      console.warn('Max custom ad attempts reached, disabling custom ads...');
      customAdsDisabled = true;
      customAdPlayed = true;
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    // Prevent multiple simultaneous ad attempts
    if (customAdShowing) {
      console.warn('Custom ad already showing, skipping...');
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    if (customAdPlayed) {
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    customAdAttempts++;

    const params = new URLSearchParams();
    if (contentId) params.append('content_id', contentId);
    if (contentType) params.append('type', contentType);
    if (currentCategoryId) params.append('category_id', currentCategoryId);
    if (contentVideoType) params.append('video_type', contentVideoType);
    const CustomapiUrl = `${baseUrl}/api/custom-ads/get-active?${params.toString()}`;
    // console.log(CustomapiUrl);
    fetch(CustomapiUrl)
      .then(res => res.json())
      .then(data => {
        // console.log(data.data);
        if (data.success && Array.isArray(data.data)) {
          const ad = data.data.find(item => item.placement === 'player' && item.status == 1);
          if (ad) {
            try {
              customAdPlayed = false;
              blockPlay = true;
              customAdShowing = true;
              player.pause();
              player.currentTime(0);
              player.el().style.pointerEvents = 'none';
            } catch (error) {
              console.error('Error setting up custom ad:', error);
              customAdShowing = false;
              blockPlay = false;
              if (typeof afterAdCallback === 'function') afterAdCallback();
              return;
            }

            function preventPlayWhileAd(e) {
              if (blockPlay) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();

                // Only pause if not already paused to prevent infinite loops
                if (!player.paused()) {
                  player.pause();
                }

                // Only set currentTime if it's not already 0
                if (player.currentTime() !== 0) {
                  player.currentTime(0);
                }

                return false;
              }
            }

                        // Use a more targeted approach to prevent infinite loops
            let isHandlingEvent = false;
            let eventCount = 0;
            const MAX_EVENTS_PER_SECOND = 5;

            function safePreventPlayWhileAd(e) {
              if (isHandlingEvent || !blockPlay) return;

              eventCount++;
              if (eventCount > MAX_EVENTS_PER_SECOND) {
                console.warn('Too many events, blocking further processing');
                return;
              }

              isHandlingEvent = true;
              try {
                preventPlayWhileAd(e);
              } finally {
                // Reset flag after a short delay to prevent rapid firing
                setTimeout(() => {
                  isHandlingEvent = false;
                }, 200);
              }
            }

            // Reset event counter every second
            setInterval(() => {
              eventCount = 0;
            }, 1000);

            // Store event listeners for cleanup
            const eventListeners = [
              { element: player.el(), event: 'play', handler: safePreventPlayWhileAd, useCapture: true },
              { element: player.el(), event: 'playing', handler: safePreventPlayWhileAd, useCapture: true }
            ];

            // Add event listeners
            eventListeners.forEach(({ element, event, handler, useCapture }) => {
              element.addEventListener(event, handler, useCapture);
            });

            // Add player event listeners with throttling
            player.on('play', safePreventPlayWhileAd);
            player.on('playing', safePreventPlayWhileAd);

            const modal = document.getElementById('customAdModal');
            const content = document.getElementById('customAdContent');
            let adHtml = '';

            let adPlayer = null;

            if (ad.type === 'image') {
              if (ad.redirect_url) {
                adHtml = `<a href="${ad.redirect_url}" target="_blank"><img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" /></a>`;
              } else {
                adHtml = `<img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" />`;
              }
            } else if (ad.type === 'video') {
              if (/youtu\.?be/.test(ad.media)) {
                let videoId = '';
                const ytMatch = ad.media.match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
                if (ytMatch && ytMatch[1]) {
                  videoId = ytMatch[1];
                }
                if (videoId) {
                  adHtml = `<div style="position:relative;width:100%;height:100%;"><iframe id="customAdVideo" width="100%" height="100%" src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.7);"></iframe>`;
                  if (ad.redirect_url) {
                    adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
                  }
                  adHtml += `</div>`;
                } else {
                  adHtml = `<div style="color:#fff;">Invalid YouTube URL</div>`;
                }
              } else {
                // Use VideoJS structure for non-YouTube videos (MP4, HLS, etc.)
                adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" class="video-js vjs-default-skin" playsinline style="width:100%;height:100%;object-fit:contain;"></video>`;
                if (ad.redirect_url) {
                  adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
                }
                adHtml += `</div>`;
              }
            }

            // Clear existing content
            content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
            content.insertAdjacentHTML('afterbegin', adHtml);
            modal.style.display = 'flex';

            // Initialize VideoJS for non-YouTube videos
            if (ad.type === 'video' && !/youtu\.?be/.test(ad.media)) {
                const videoUrl = ad.url_type === 'url' ? ad.media : `${baseUrl}${ad.media}`;
                const isHls = videoUrl.includes('.m3u8');
                const mimeType = isHls ? 'application/x-mpegURL' : 'video/mp4';

                // Dispose if exists (safety check)
                if (videojs.getPlayer('customAdVideo')) {
                    videojs.getPlayer('customAdVideo').dispose();
                }

                adPlayer = videojs('customAdVideo', {
                    controls: false,
                    autoplay: true,
                    preload: 'auto',
                    sources: [{ src: videoUrl, type: mimeType }]
                });

                adPlayer.on('ended', () => {
                    closeCustomAdModal();
                });

                adPlayer.on('error', () => {
                    console.error('Custom ad playback error:', adPlayer.error());
                    closeCustomAdModal();
                });
            }

            const closeBtn = document.getElementById('customAdCloseBtn');
            closeBtn.onclick = () => {
              closeCustomAdModal();
            };

            if (ad.type === 'image') {
              setTimeout(() => {
                if (modal.style.display === 'flex') {
                  closeCustomAdModal();
                }
              }, 5000000);
            }
            
            // For YouTube iframe, we can't easily detect end, so we rely on user closing or timeout? 
            // The previous code didn't have auto-close for YouTube either except manual close.

            function closeCustomAdModal() {
              if (adPlayer) {
                  adPlayer.dispose();
                  adPlayer = null;
              }
              modal.style.display = 'none';
              content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());

                            // Clean up event listeners
              eventListeners.forEach(({ element, event, handler, useCapture }) => {
                element.removeEventListener(event, handler, useCapture);
              });

              player.off('play', safePreventPlayWhileAd);
              player.off('playing', safePreventPlayWhileAd);

              player.el().style.pointerEvents = '';
              blockPlay = false;
              customAdPlayed = true;
              customAdShowing = false;
              markPrerollShown();

              if (typeof afterAdCallback === 'function') afterAdCallback();
            }

            // Add click handler for overlay if present
            if (ad.redirect_url) {
              setTimeout(() => {
                const overlay = document.getElementById('adClickOverlay');
                if (overlay) {
                  overlay.onclick = () => {
                    window.open(ad.redirect_url, '_blank');
                  };
                }
              }, 0);
            }

            return;
          }
        }

        // ✅ No custom ad → proceed to VAST anyway
        if (typeof afterAdCallback === 'function') afterAdCallback();
      })
      .catch((error) => {
        console.error('Error fetching custom ads:', error);
        // ✅ Fetch failed → still proceed to VAST
        if (typeof afterAdCallback === 'function') afterAdCallback();
      });

    // // Add timeout to prevent hanging
    // setTimeout(() => {
    //   if (customAdShowing && !customAdPlayed) {
    //     console.warn('Custom ad timeout, forcing cleanup...');
    //     customAdShowing = false;
    //     blockPlay = false;
    //     customAdPlayed = true;
    //     if (typeof afterAdCallback === 'function') afterAdCallback();
    //   }
    // }, 30000); // 30 second timeout
  }


  function markAdCuesForAvailableAds() {
    const duration = player.duration();
    // console.log('Duration check:', duration, 'isNaN:', isNaN(duration));

    // Better duration validation with retry
    if (!duration || isNaN(duration) || duration <= 0) {
      // console.log('Invalid duration, retrying in 1 second...');
      setTimeout(() => markAdCuesForAvailableAds(), 1000);
      return;
    }

    // Clear any existing ad cues EXCEPT overlay cues
    const existingCues = player.el().querySelectorAll('.vjs-ad-cue');
    existingCues.forEach(cue => {
      if (!cue.title.includes('Overlay')) {
        cue.remove();
      }
    });

    // Reset mid-roll time
    midRollTime = 0;

    // Mark cues only for ads that are actually available
    adQueue.forEach((ad, index) => {
      switch (ad.type) {
        case 'mid-roll':
          midRollTime = Math.floor(duration / 2);
          markAdCue(midRollTime, 'Mid-roll Ad');
          debugLog('Set mid-roll time', { midRollTime, duration });
          break;
        case 'post-roll':
          markAdCue(Math.floor(duration * 0.9), 'Post-roll Ad');
          break;
        // Overlay cues are handled in scheduleOverlayAds and not removed here
        default:
          break;
      }
    });

    debugLog('Marked ad cues for available ads', {
      totalAds: adQueue.length,
      adTypes: adQueue.map(a => a.type),
      midRollTime: midRollTime,
      hasMidRollAds: adQueue.filter(a => a.type === 'mid-roll').length > 0
    });
  }


  function startPlaybackInterval() {
    if (playbackInterval) clearInterval(playbackInterval);
    // Mid-roll / post-roll / overlay ads intentionally disabled — only pre-roll plays.
  }

  player.ready(function () {
    debugLog('Player ready');
    // console.log('Video source:', player.src());
    // console.log('Video readyState:', player.readyState());

    // Monitor for excessive errors and disable custom ads if needed
    let errorCount = 0;
    const MAX_ERRORS = 50;

    const originalConsoleError = console.error;
    console.error = function(...args) {
      errorCount++;
      if (errorCount > MAX_ERRORS && !customAdsDisabled) {
        console.warn('Too many errors detected, disabling custom ads');
        customAdsDisabled = true;
        customAdPlayed = true;
        blockPlay = false;
        customAdShowing = false;
      }
      originalConsoleError.apply(console, args);
    };

    // IMA setup
    const adsRenderingSettings = new google.ima.AdsRenderingSettings();
    adsRenderingSettings.enablePreloading = true;
    adsRenderingSettings.uiElements = [
      google.ima.UiElements.AD_ATTRIBUTION,
      google.ima.UiElements.COUNTDOWN,
    ];
    adsRenderingSettings.useStyledLinearAds = true;

    player.ima({
      id: 'videoPlayer',
      adTagUrl: '',
      debug: true,
      showControlsForJSAds: true,
      adsRenderingSettings: adsRenderingSettings,
      disableCustomPlaybackForIOS10Plus: true,
      contribAdsSettings: {
        prerollTimeout: 1000,
        postrollTimeout: 1000,
        disablePlayContentBehindAd: true
      }
    });

    let customAdChecked = false;
    player.one('play', function () {
      if (!customAdPlayed && !customAdChecked) {
        customAdChecked = true;
        player.pause();
        showCustomAdThenPlayMain(function () {
          player.ima.initializeAdDisplayContainer();
          loadAdsAndStartInterval(); // ✅ NEW central logic
        });
        return;
      }

      player.ima.initializeAdDisplayContainer();
      loadAdsAndStartInterval(); // ✅ in case no custom ad
    });

    player.on('ended', function () {
      debugLog('Video ended');
      hideSkipButton();
      if (playbackInterval) clearInterval(playbackInterval);
    });

    player.on('loadedmetadata', function () {
      // console.log('Metadata loaded, duration:', player.duration());
      scheduleOverlayAds();
      if (adQueue.length > 0) {
        markAdCuesForAvailableAds();
      }
    });

    player.on('durationchange', function () {
      // console.log('Duration changed:', player.duration());
      if (adQueue.length > 0) {
        markAdCuesForAvailableAds();
      }
    });

    // Debug interval for tracking playback
    setInterval(() => {
      if (player.duration() > 0) {
        // console.log('DEBUG - Current time:', player.currentTime(), 'Duration:', player.duration(), 'Mid-roll time:', midRollTime);
      }
    }, 5000);
  });

  function loadAdsAndStartInterval() {
    const params = new URLSearchParams();
    if (contentId) params.append('content_id', contentId);
    if (contentType) params.append('type', contentType);
    if (contentVideoType) params.append('video_type', contentVideoType);
    if (currentCategoryId) params.append('category_id', currentCategoryId);
    if (currentEpisodeId) params.append('episode_id', currentEpisodeId);
    const apiUrl = `${baseUrl}/api/vast-ads/get-active?${params.toString()}`;
    // console.log('Loading ads from API', apiUrl);

    fetch(apiUrl)
      .then(res => res.json())
      .then(response => {
        // console.log('Ads API response', response);
        if (!response.success || !response.data) throw new Error('No ads');
        adQueue = (response.data || []).filter(a => a && a.type === 'pre-roll');
        overlayAds = [];
        // console.log('Loaded ad types:', adQueue.map(a => a.type));

        const prerollAlreadyDone = isPrerollAlreadyShown();
        const hasPreRoll = !prerollAlreadyDone && adQueue.some(ad => ad.type === 'pre-roll');

        // Wait for metadata to be loaded before scheduling ads
        if (player.readyState() >= 1) {
          markAdCuesForAvailableAds();
          scheduleOverlayAds();
        } else {
          player.one('loadedmetadata', () => {
            markAdCuesForAvailableAds();
            scheduleOverlayAds();
          });
        }

        // Ensure playback interval starts after ads setup
        const startVideo = () => {
          player.play();
          // console.log("[AD DEBUG] Starting playback interval");
          startPlaybackInterval();
        };

        // Pre-roll logic
        if (hasPreRoll) {
          playAdsByType('pre-roll', function () {
            markPrerollShown();
            startVideo();
          });
        } else {
          startVideo();
        }
      })
      .catch(err => {
        // console.log('[AD DEBUG] Ad fetch error', err);
        player.play();
        startPlaybackInterval();
      });
  }
});
