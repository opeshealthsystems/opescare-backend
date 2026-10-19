// k6 load test for the public finder surface (no auth, no writes).
//
//   k6 run -e BASE_URL=https://staging.finder.opescare.cloud scripts/load/finder-smoke.k6.js
//   k6 run -e BASE_URL=... -e PROFILE=soak scripts/load/finder-smoke.k6.js
//
// NEVER point this at production without the owner's go-ahead: the public
// routes are throttled per IP and a load test from one box will trip them
// (expect 429s — they are counted separately, not as failures).
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const PROFILE = __ENV.PROFILE || 'smoke';

const profiles = {
  smoke:  { stages: [{ duration: '30s', target: 5 }] },
  load:   { stages: [{ duration: '2m', target: 50 }, { duration: '5m', target: 50 }, { duration: '1m', target: 0 }] },
  stress: { stages: [{ duration: '2m', target: 100 }, { duration: '3m', target: 300 }, { duration: '2m', target: 0 }] },
  soak:   { stages: [{ duration: '5m', target: 40 }, { duration: '60m', target: 40 }, { duration: '5m', target: 0 }] },
};

export const options = {
  ...profiles[PROFILE],
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{name:directory}': ['p(95)<400'],
    'http_req_duration{name:medicine}':  ['p(95)<500'],
    'http_req_duration{name:blood}':     ['p(95)<400'],
    throttled: ['rate<0.05'],
  },
};

const throttled = new Rate('throttled');
const serverTiming = new Trend('payload_bytes');

// Cameroon's main cities, so the bounding box lands on real density.
const CITIES = [
  [3.848, 11.502], [4.051, 9.768], [5.963, 10.159], [9.301, 13.398],
  [5.478, 10.418], [10.591, 14.316], [7.327, 13.584], [4.577, 13.684],
];
const TERMS = ['paracetamol', 'amoxicilline', 'ibuprofen', 'artemether', 'metformin'];
const WORDS = ['clinique', 'pharmacie', 'hopital', 'dentiste', 'laboratoire', 'maternite'];
const GROUPS = ['O+', 'O-', 'A+', 'B+', 'AB+'];

const pick = (a) => a[Math.floor(Math.random() * a.length)];

function get(url, name) {
  const res = http.get(url, { tags: { name }, headers: { Accept: 'application/json' } });
  throttled.add(res.status === 429);
  if (res.status !== 429) {
    check(res, { [`${name} 200`]: (r) => r.status === 200 });
  }
  serverTiming.add(res.body ? res.body.length : 0);
  return res;
}

export default function () {
  const [lat, lng] = pick(CITIES);

  group('directory', () => {
    get(`${BASE}/api/v1/care-map/search?query=${pick(WORDS)}&latitude=${lat}&longitude=${lng}&radius=25`, 'directory');
  });
  group('medicine', () => {
    get(`${BASE}/api/v1/care-map/pharmacies/medicine-search?medicine=${pick(TERMS)}&latitude=${lat}&longitude=${lng}&radius=25`, 'medicine');
  });
  group('blood', () => {
    get(`${BASE}/api/v1/care-map/blood/search?blood_group=${encodeURIComponent(pick(GROUPS))}&latitude=${lat}&longitude=${lng}&radius=50`, 'blood');
  });

  sleep(1 + Math.random() * 2);
}
