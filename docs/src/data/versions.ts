export interface Version {
  version: string;
  label: string;
  path?: string;
  isLatest?: boolean;
}

export const VERSIONS: Version[] = [
  {
    version: 'latest',
    label: 'Latest',
    isLatest: true,
  },
  // Future versions will be added here
  // {
  //   version: '0.5.0',
  //   label: 'v0.5.0',
  //   path: '/v0.5/',
  // },
  // {
  //   version: '0.4.0',
  //   label: 'v0.4.0',
  //   path: '/v0.4/',
  // },
];

export const getCurrentVersion = (): Version => {
  return VERSIONS.find(v => v.isLatest) || VERSIONS[0];
};
