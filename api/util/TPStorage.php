<?php

class TPStorage {

  function getSiteSettings() {
    $ss = new TPSiteSettings(get_option(TPSiteSettings::TINYPASS_SITE_SETTINGS));
    return $ss;
  }

  function saveSiteSettings(TPSiteSettings $ss) {
    wp_cache_delete(TPSiteSettings::TINYPASS_SITE_SETTINGS);
    update_option(TPSiteSettings::TINYPASS_SITE_SETTINGS, $ss->toArray());
  }

  /**
   * Individual paywall settings
   */
  function getPaywalls($showDisabled = false) {
    $ss = $this->getSiteSettings();
    $paywallNames = $ss->getPaywalls();
    $data = array();
    foreach ($paywallNames as $rid) {
      $ps = new TPPaySettings(get_option("tinypass_" . $rid));
      if ($showDisabled || $ps->isEnabled())
        $data[$rid] = new TPPaySettings(get_option("tinypass_" . $rid));
    }
    return $data;
  }

  function getPaywall($name = null, $showDisabled = false) {
    $paywalls = $this->getPaywalls($showDisabled);

    foreach ($paywalls as $rid => $ps) {
      if ($rid == $name)
        return $ps;
    }

    $ps = new TPPaySettings(array());
    $ps->setResourceId($name);
    return $ps;
  }

  function savePaywallSettings(TPSiteSettings $ss, TPPaySettings $pw) {
    $ss->addPaywall($pw);
    $this->saveSiteSettings($ss);

    wp_cache_delete("tinypass_" . $pw->getResourceId());
    update_option("tinypass_" . $pw->getResourceId(), $pw->toArray());
  }


  function getPaywallSubRefID($ss, $postID) {
    $walls = $this->getPaywalls();
    foreach ($walls as $id => $pw) {
      if ($pw->getSubscriptionPageRef() == $postID) {
        return $pw;
      }
    }
    return new TPPaySettings(array());
  }

  function getPaywallByTag($ss, $postID) {
    $post_terms = wp_get_post_terms($postID, 'post_tag', array());
    $walls = $this->getPaywalls(true);
    foreach ($post_terms as $term) {
      foreach ($walls as $id => $pw) {
        if ($pw->tagMatches($term->name)) {
          return $pw;
        }
      }
    }
    return new TPPaySettings(array());
  }

  /**
   *  Post releated meta data
   */
  function getPostSettings($postID) {
    $meta = get_post_meta($postID, 'tinypass', true);
    return new TPPaySettings($meta);
  }

  function savePostSettings($postID, $ps) {
    delete_post_meta($postID, 'tinypass');
    update_post_meta($postID, 'tinypass', $ps->toArray(), true);
  }

}

?>