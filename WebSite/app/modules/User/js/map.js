document.addEventListener('DOMContentLoaded', function () {
  const map = L.map('memberMap', {
    maxZoom: 12
  }).setView([47.3220, 5.0415], 10);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  function createPopupContent(member) {
    let content = `<div class="text-center mb-2">
            <strong>${member.name}</strong>`;

    if (member.nickname) {
      content += `<br><span class="text-muted">${member.nickname}</span>`;
    }

    if (member.useGravatar == 'yes' && member.email) {
      const emailHash = md5(member.email.trim().toLowerCase());
      const gravatarUrl = `https://www.gravatar.com/avatar/${emailHash}?s=64&d=mp`;
      content += `<br><img src="${gravatarUrl}" alt="${member.name}" class="img-fluid rounded-circle mt-2" style="max-width: 64px; max-height: 64px;">`;
    } else if (member.avatar) {
      content += `<br>
                  <span class="d-inline-flex align-items-center justify-content-center rounded-circle mt-2" 
                    style="font-size: 48px; width: 64px; height: 64px; line-height: 64px; text-align: center; background-color: #f0f0f0;">
                      ${member.avatar}
                  </span>`;
    }
    content += `</div>`;
    return content;
  }

  const markers = L.markerClusterGroup({
    iconCreateFunction: function (cluster) {
      const count = cluster.getChildCount();
      return L.divIcon({
        html: `<div style="font-size:36px; font-weight:bold;">${count}</div>`,
        className: 'custom-cluster',
        iconSize: L.point(50, 50)
      });
    }
  });

  locationData.forEach(member => {
    const marker = L.marker([parseFloat(member.lat), parseFloat(member.lng)])
      .bindPopup(createPopupContent(member));
    markers.addLayer(marker);
  });

  map.addLayer(markers);

  if (locationData.length > 0) {
    const latLngs = locationData.map(member => [parseFloat(member.lat), parseFloat(member.lng)]);
    map.fitBounds(latLngs);
  }
});
